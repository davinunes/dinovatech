<?php
// inter/PixAutomaticoService.php

require_once __DIR__ . '/api.php';
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/../database.php';
require_once __DIR__ . '/../dinovatech/helpers/AppHelper.php';

class PixAutomaticoService
{
    /**
     * Executa o fluxo completo da Jornada 4 do Banco Inter para uma fatura:
     * Passo 1: POST /locrec (Criação da Location de Recorrência)
     * Passo 2: PUT /cobv/{txid} (Criação da Cobrança com Vencimento da Fatura Atual)
     * Passo 3: POST /rec (Criação do Contrato de Recorrência)
     * Passo 4: GET /rec?idRec={idRec}&txid={txid} (Geração do QR Code Combinado)
     * 
     * @param int $idFatura ID da Fatura no Dinovatech
     * @param mysqli $link Conexão ativa com o banco de dados
     * @return array Resultado com o QR Code e identificadores
     */
    public static function gerarJornada4ParaFatura($idFatura, $link)
    {
        global $ambienteConfig, $sslCertFile, $sslKeyFile, $caInfoFile;

        $idFaturaSafe = (int) $idFatura;
        if ($idFaturaSafe <= 0) {
            throw new Exception("ID da fatura inválido.");
        }

        // 1. Busca dados da fatura e cliente
        $qFatura = "SELECT F.*, C.id_cliente, C.nome AS nome_cliente, C.cpf_cnpj, C.email AS email_cliente,
                           C.endereco, C.numero, C.complemento, C.bairro, C.cep, C.uf, C.codigo_municipio
                    FROM Faturas F
                    JOIN Clientes C ON F.id_cliente = C.id_cliente
                    WHERE F.id_fatura = $idFaturaSafe LIMIT 1";
        $resFatura = DBExecute($link, $qFatura);
        if (!$resFatura) {
            throw new Exception("Erro ao consultar dados da fatura #{$idFaturaSafe}: " . mysqli_error($link));
        }
        $fatura = mysqli_fetch_assoc($resFatura);

        if (!$fatura) {
            throw new Exception("Fatura #{$idFaturaSafe} não encontrada.");
        }

        if ($fatura['status'] === 'Liquidada' || $fatura['status'] === 'Cancelada') {
            throw new Exception("Fatura já está com status '{$fatura['status']}'.");
        }

        // 2. Localiza o contrato (Recorrencia) vinculado através dos itens da fatura
        $qItemRec = "SELECT id_recorrencia FROM ItensFatura 
                     WHERE id_fatura = $idFaturaSafe AND id_recorrencia IS NOT NULL AND id_recorrencia > 0 
                     LIMIT 1";
        $resItemRec = DBExecute($link, $qItemRec);
        $itemRec = mysqli_fetch_assoc($resItemRec);
        $idRecorrencia = !empty($itemRec['id_recorrencia']) ? (int) $itemRec['id_recorrencia'] : null;

        // Se não tiver item com id_recorrencia, tenta buscar o contrato mais recente do cliente
        if (!$idRecorrencia) {
            $qContratoCliente = "SELECT id_recorrencia FROM Recorrencias 
                                 WHERE id_cliente = {$fatura['id_cliente']} 
                                 ORDER BY id_recorrencia DESC LIMIT 1";
            $resContratoCliente = DBExecute($link, $qContratoCliente);
            if ($resContratoCliente && mysqli_num_rows($resContratoCliente) > 0) {
                $rowContrato = mysqli_fetch_assoc($resContratoCliente);
                $idRecorrencia = (int) $rowContrato['id_recorrencia'];
            }
        }

        if (!$idRecorrencia) {
            throw new Exception("Não foi encontrado nenhum contrato de recorrência vinculado a esta fatura ou cliente.");
        }

        // Busca dados do contrato
        $qContrato = "SELECT R.*, S.nome_servico 
                      FROM Recorrencias R 
                      JOIN Servicos S ON R.id_servico = S.id_servico 
                      WHERE R.id_recorrencia = $idRecorrencia LIMIT 1";
        $resContrato = DBExecute($link, $qContrato);
        $contrato = mysqli_fetch_assoc($resContrato);
        if (!$contrato) {
            throw new Exception("Contrato de recorrência #{$idRecorrencia} não encontrado.");
        }

        // 3. Calcula o saldo líquido da fatura
        $calcTotals = AppHelper::calculateFaturaTotals($link, $idFaturaSafe);
        $qPago = "SELECT SUM(valor_pago) AS total_pago FROM Pagamentos WHERE id_fatura = $idFaturaSafe AND status_pagamento = 'Confirmado'";
        $resPago = DBExecute($link, $qPago);
        $rowPago = mysqli_fetch_assoc($resPago);
        $totalPago = (float) ($rowPago['total_pago'] ?? 0.00);
        $saldoDevedor = $calcTotals['valor_liquido'] - $totalPago;

        if ($saldoDevedor <= 0) {
            throw new Exception("Fatura já está totalmente liquidada.");
        }

        $valorCobranca = number_format($saldoDevedor, 2, '.', '');
        $valorRecorrente = number_format((float)($contrato['valor_sugerido_recorrencia'] ?? $saldoDevedor) * (int)($contrato['quantidade'] ?? 1), 2, '.', '');

        // 4. Obtém Token OAuth2 do Inter
        $token = getInterAccessToken($ambienteConfig, $sslCertFile, $sslKeyFile, $caInfoFile);

        // Prepara Devedor
        $docLimpo = preg_replace('/[^0-9]/', '', $fatura['cpf_cnpj']);
        $devedorPayload = ['nome' => $fatura['nome_cliente']];
        if (strlen($docLimpo) === 11) {
            $devedorPayload['cpf'] = $docLimpo;
        } elseif (strlen($docLimpo) === 14) {
            $devedorPayload['cnpj'] = $docLimpo;
        } else {
            throw new Exception("CPF/CNPJ do cliente é inválido ({$fatura['cpf_cnpj']}).");
        }

        // ==========================================
        // PASSO 1: Criar Location (POST /loc com tipoCob: cobv)
        // ==========================================
        try {
            $locResponse = criarLocationRecorrencia($ambienteConfig, $sslCertFile, $sslKeyFile, $caInfoFile, $token, 'cobv');
        } catch (Exception $e) {
            // Se o token em sessão expirou ou não continha os novos escopos, força nova emissão do token e retenta
            if (strpos($e->getMessage(), '401') !== false || strpos($e->getMessage(), 'scope') !== false) {
                $token = getInterAccessToken($ambienteConfig, $sslCertFile, $sslKeyFile, $caInfoFile, true);
                $locResponse = criarLocationRecorrencia($ambienteConfig, $sslCertFile, $sslKeyFile, $caInfoFile, $token);
            } else {
                throw $e;
            }
        }
        $idLocation = (int) $locResponse->id;

        // ==========================================
        // PASSO 2: Criar CobV (/cobv/{txid})
        // ==========================================
        // Gera um TXID único válido para CobV (26 a 35 caracteres alfanuméricos)
        $txidCobv = 'DVT' . date('Ymd') . sprintf('%06d', $idFaturaSafe) . substr(md5(uniqid(rand(), true)), 0, 10);
        $txidCobv = substr(preg_replace('/[^a-zA-Z0-9]/', '', $txidCobv), 0, 35);

        $dataVencimentoFatura = !empty($fatura['data_vencimento']) ? date('Y-m-d', strtotime($fatura['data_vencimento'])) : date('Y-m-d');
        // Se a data de vencimento for no passado, ajusta para hoje para permitir pagamento imediato
        if ($dataVencimentoFatura < date('Y-m-d')) {
            $dataVencimentoFatura = date('Y-m-d');
        }

        $dadosCobv = [
            'calendario' => [
                'dataDeVencimento' => $dataVencimentoFatura,
                'validadeAposVencimento' => 30
            ],
            'devedor' => $devedorPayload,
            'valor' => [
                'original' => $valorCobranca
            ],
            'chave' => $ambienteConfig['chave_pix'],
            'solicitacaoPagador' => "Dinovatech Fatura #{$idFaturaSafe}",
            'loc' => [
                'id' => $idLocation
            ]
        ];

        $cobvResponse = criarCobvComVencimento($ambienteConfig, $sslCertFile, $sslKeyFile, $caInfoFile, $token, $txidCobv, $dadosCobv);

        // ==========================================
        // PASSO 3: Criar Recorrência (/rec)
        // ==========================================
        // Define data inicial para o próximo mês a partir do vencimento
        $dtVencObj = new DateTime($dataVencimentoFatura);
        $dtVencObj->modify('+1 month');
        $dataInicialRec = $dtVencObj->format('Y-m-01');

        $dataFinalRec = null;
        if (!empty($contrato['data_fim_cobranca'])) {
            $dfCandidate = date('Y-m-d', strtotime($contrato['data_fim_cobranca']));
            // Só envia dataFinal se for no futuro e posterior à data inicial
            if ($dfCandidate > date('Y-m-d') && $dfCandidate > $dataInicialRec) {
                $dataFinalRec = $dfCandidate;
            }
        }

        $objetoDesc = "Dinovatech Contrato #{$idRecorrencia}";

        $dadosRec = [
            'vinculo' => [
                'contrato' => (string) $idRecorrencia,
                'devedor' => $devedorPayload,
                'objeto' => $objetoDesc
            ],
            'calendario' => [
                'dataInicial' => $dataInicialRec,
                'periodicidade' => 'MENSAL'
            ],
            'valor' => [
                'valorRec' => $valorRecorrente
            ],
            'politicaRetentativa' => 'PERMITE_3R_7D',
            'loc' => (int) $idLocation
        ];

        if (!empty($dataFinalRec)) {
            $dadosRec['calendario']['dataFinal'] = $dataFinalRec;
        }

        $recResponse = criarRecorrenciaContrato($ambienteConfig, $sslCertFile, $sslKeyFile, $caInfoFile, $token, $dadosRec);
        $idRec = $recResponse->idRec;

        // ==========================================
        // PASSO 4: Consultar QR Code Jornada 4 (/rec?idRec&txid)
        // ==========================================
        $jornada4Response = consultarRecorrenciaJornada4($ambienteConfig, $sslCertFile, $sslKeyFile, $caInfoFile, $token, $idRec, $txidCobv);

        // Extrai o Pix Copia e Cola / Payload combinado
        $pixCopiaECola = $jornada4Response->pixCopiaECola ?? ($jornada4Response->emv ?? ($cobvResponse->pixCopiaECola ?? ''));
        $calendarioCombined = $jornada4Response->calendario ?? ($cobvResponse->calendario ?? []);

        // ==========================================
        // 5. Persiste no Banco de Dados
        // ==========================================
        $idRecSafe = mysqli_real_escape_string($link, $idRec);
        $txidCobvSafe = mysqli_real_escape_string($link, $txidCobv);
        $qrCodeSafe = mysqli_real_escape_string($link, $pixCopiaECola);
        $jsonPayloadSafe = mysqli_real_escape_string($link, json_encode($jornada4Response, JSON_UNESCAPED_UNICODE));
        $dataFinalSql = !empty($dataFinalRec) ? "'$dataFinalRec'" : "NULL";

        // Insere ou atualiza na tabela PixRecorrencias
        $qUpsertPixRec = "INSERT INTO PixRecorrencias 
                            (id_recorrencia, id_cliente, id_fatura_inicial, id_rec, id_location, txid_inicial, status, valor_recorrente, data_inicial, data_final, qr_code_adesao, dados_bancarios_json, data_criacao, data_ultima_consulta)
                          VALUES 
                            ($idRecorrencia, {$fatura['id_cliente']}, $idFaturaSafe, '$idRecSafe', $idLocation, '$txidCobvSafe', 'PENDENTE', $valorRecorrente, '$dataInicialRec', $dataFinalSql, '$qrCodeSafe', '$jsonPayloadSafe', NOW(), NOW())
                          ON DUPLICATE KEY UPDATE 
                            id_fatura_inicial = $idFaturaSafe,
                            id_location = $idLocation,
                            txid_inicial = '$txidCobvSafe',
                            status = 'PENDENTE',
                            valor_recorrente = $valorRecorrente,
                            data_inicial = '$dataInicialRec',
                            data_final = $dataFinalSql,
                            qr_code_adesao = '$qrCodeSafe',
                            dados_bancarios_json = '$jsonPayloadSafe',
                            data_ultima_consulta = NOW()";
        DBExecute($link, $qUpsertPixRec);

        // Insere na tabela Pagamentos para controle da fatura atual
        $calJsonSafe = mysqli_real_escape_string($link, json_encode($calendarioCombined));
        $qInsertPag = "INSERT INTO Pagamentos (id_fatura, valor_pago, data_pagamento, status_pagamento, txid, cod_qrcode, calendario, observacao)
                       VALUES ($idFaturaSafe, '$valorCobranca', CURDATE(), 'Pendente', '$txidCobvSafe', '$qrCodeSafe', '$calJsonSafe', 'Pix Automático Jornada 4 - Recorrência {$idRecSafe}')";
        DBExecute($link, $qInsertPag);

        return [
            'success' => true,
            'idRec' => $idRec,
            'idLocation' => $idLocation,
            'txid' => $txidCobv,
            'pixCopiaECola' => $pixCopiaECola,
            'calendario' => $calendarioCombined,
            'valorFatura' => $valorCobranca,
            'valorRecorrente' => $valorRecorrente,
            'dataVencimento' => $dataVencimentoFatura,
            'raw' => $jornada4Response
        ];
    }

    /**
     * Consulta ativamente o status da recorrência no Banco Inter e atualiza no banco local.
     * 
     * @param string $idRec Identificador da recorrência (RN...)
     * @param mysqli $link Conexão ativa com o banco
     * @return array Status atualizado
     */
    public static function sincronizarStatusRecorrencia($idRec, $link)
    {
        global $ambienteConfig, $sslCertFile, $sslKeyFile, $caInfoFile;

        $idRec = trim($idRec);
        if (empty($idRec)) {
            throw new Exception("Identificador idRec é obrigatório.");
        }

        $token = getInterAccessToken($ambienteConfig, $sslCertFile, $sslKeyFile, $caInfoFile);
        $interResponse = consultarStatusRecorrencia($ambienteConfig, $sslCertFile, $sslKeyFile, $caInfoFile, $token, $idRec);

        $statusInter = strtoupper($interResponse->status ?? 'PENDENTE');
        $idRecSafe = mysqli_real_escape_string($link, $idRec);
        $statusSafe = mysqli_real_escape_string($link, $statusInter);
        $jsonSafe = mysqli_real_escape_string($link, json_encode($interResponse, JSON_UNESCAPED_UNICODE));

        $dataAceiteSql = ($statusInter === 'APROVADA') ? "data_aceite = COALESCE(data_aceite, NOW())," : "";

        $qUpdate = "UPDATE PixRecorrencias 
                    SET status = '$statusSafe',
                        $dataAceiteSql
                        dados_bancarios_json = '$jsonSafe',
                        data_ultima_consulta = NOW()
                    WHERE id_rec = '$idRecSafe'";
        DBExecute($link, $qUpdate);

        return [
            'success' => true,
            'idRec' => $idRec,
            'status' => $statusInter,
            'data' => $interResponse
        ];
    }

    /**
     * Dispara uma cobrança subsequente (POST /cobr) para débito automático no dia de vencimento.
     * 
     * @param int $idFatura ID da nova fatura mensal criada
     * @param int $idRecorrencia ID do contrato
     * @param mysqli $link Conexão ativa com o banco
     * @return array Resposta da API do Inter com txid
     */
    public static function processarCobrancaSubsequente($idFatura, $idRecorrencia, $link)
    {
        global $ambienteConfig, $sslCertFile, $sslKeyFile, $caInfoFile;

        $idFatura = (int) $idFatura;
        $idRecorrencia = (int) $idRecorrencia;

        // Busca dados da recorrência ativa
        $qRec = "SELECT * FROM PixRecorrencias WHERE id_recorrencia = $idRecorrencia AND status = 'APROVADA' LIMIT 1";
        $resRec = DBExecute($link, $qRec);
        $pixRec = mysqli_fetch_assoc($resRec);

        if (!$pixRec) {
            throw new Exception("Contrato #{$idRecorrencia} não possui Pix Automático no status APROVADA.");
        }

        // Busca dados da fatura e cliente
        $qFatura = "SELECT F.*, C.nome AS nome_cliente, C.cpf_cnpj, C.email AS email_cliente,
                           C.endereco, C.numero, C.complemento, C.bairro, C.cep, C.uf, C.codigo_municipio
                    FROM Faturas F
                    JOIN Clientes C ON F.id_cliente = C.id_cliente
                    WHERE F.id_fatura = $idFatura LIMIT 1";
        $resFatura = DBExecute($link, $qFatura);
        if (!$resFatura) {
            throw new Exception("Erro ao consultar dados da fatura #{$idFatura}: " . mysqli_error($link));
        }
        $fatura = mysqli_fetch_assoc($resFatura);
        if (!$fatura) {
            throw new Exception("Fatura #{$idFatura} não encontrada.");
        }

        $calcTotals = AppHelper::calculateFaturaTotals($link, $idFatura);
        $valorOriginal = number_format($calcTotals['valor_liquido'], 2, '.', '');
        $dataVencimento = !empty($fatura['data_vencimento']) ? date('Y-m-d', strtotime($fatura['data_vencimento'])) : date('Y-m-d');

        // Busca dados da empresa emissora (recebedor)
        $qEmissor = "SELECT api_inter_conta_corrente, razao_social, cnpj FROM ConfiguracoesEmissor LIMIT 1";
        $resEmissor = DBExecute($link, $qEmissor);
        $emissor = mysqli_fetch_assoc($resEmissor);
        $contaRecebedor = preg_replace('/[^0-9]/', '', $emissor['api_inter_conta_corrente'] ?? $ambienteConfig['conta_corrente'] ?? '000000');

        $docLimpo = preg_replace('/[^0-9]/', '', $fatura['cpf_cnpj']);
        $cidadeNome = ($fatura['uf'] === 'DF' || ($fatura['codigo_municipio'] ?? '') === '5300108') ? 'Brasília' : 'Brasília';
        $devedorData = [
            'cep' => preg_replace('/[^0-9]/', '', $fatura['cep'] ?: '70000000'),
            'cidade' => $cidadeNome,
            'email' => $fatura['email_cliente'] ?: 'cliente@dinovatech.com.br',
            'logradouro' => ($fatura['endereco'] ?: 'Endereço') . ($fatura['numero'] ? ', ' . $fatura['numero'] : ''),
            'uf' => $fatura['uf'] ?: 'DF'
        ];

        $payloadCobr = [
            'idRec' => $pixRec['id_rec'],
            'infoAdicional' => "Fatura #{$idFatura} - Ref. " . date('m/Y', strtotime($dataVencimento)),
            'calendario' => [
                'dataDeVencimento' => $dataVencimento
            ],
            'valor' => [
                'original' => $valorOriginal
            ],
            'ajusteDiaUtil' => true,
            'devedor' => $devedorData,
            'recebedor' => [
                'agencia' => '0001',
                'conta' => $contaRecebedor,
                'tipoConta' => 'CORRENTE'
            ]
        ];

        $token = getInterAccessToken($ambienteConfig, $sslCertFile, $sslKeyFile, $caInfoFile);
        $cobrResponse = criarCobrancaRecorrenteSubsequente($ambienteConfig, $sslCertFile, $sslKeyFile, $caInfoFile, $token, $payloadCobr);

        $txid = $cobrResponse->txid ?? ('COBR_' . uniqid());
        $txidSafe = mysqli_real_escape_string($link, $txid);
        $obsSafe = mysqli_real_escape_string($link, "Débito Automático Pix Agendado - Recorrência {$pixRec['id_rec']}");

        // Registra o débito agendado em Pagamentos
        $qPag = "INSERT INTO Pagamentos (id_fatura, valor_pago, data_pagamento, status_pagamento, txid, observacao)
                 VALUES ($idFatura, '$valorOriginal', '$dataVencimento', 'Pendente', '$txidSafe', '$obsSafe')";
        DBExecute($link, $qPag);

        return [
            'success' => true,
            'idFatura' => $idFatura,
            'idRec' => $pixRec['id_rec'],
            'txid' => $txid,
            'dataVencimento' => $dataVencimento,
            'valor' => $valorOriginal,
            'raw' => $cobrResponse
        ];
    }

    /**
     * Cancela uma recorrência ativa (Contrato de Pix Automático) no Banco Inter e atualiza o banco local.
     * 
     * @param string $idRec Identificador da recorrência (RN...)
     * @param string $motivo Motivo do cancelamento
     * @param mysqli $link Conexão ativa com o banco
     * @return array Resposta da operação
     */
    public static function cancelarRecorrenciaContratoService($idRec, $motivo = 'Cancelamento solicitado', $link = null)
    {
        global $ambienteConfig, $sslCertFile, $sslKeyFile, $caInfoFile;

        $idRec = trim($idRec);
        if (empty($idRec)) {
            throw new Exception("Identificador idRec é obrigatório.");
        }

        $token = getInterAccessToken($ambienteConfig, $sslCertFile, $sslKeyFile, $caInfoFile);
        $responseInter = cancelarRecorrenciaContrato($ambienteConfig, $sslCertFile, $sslKeyFile, $caInfoFile, $token, $idRec);

        if ($link) {
            $idRecSafe = mysqli_real_escape_string($link, $idRec);
            $motivoSafe = mysqli_real_escape_string($link, $motivo);
            $jsonSafe = mysqli_real_escape_string($link, json_encode($responseInter, JSON_UNESCAPED_UNICODE));

            $qUpdate = "UPDATE PixRecorrencias 
                        SET status = 'CANCELADA',
                            motivo_cancelamento = '$motivoSafe',
                            dados_bancarios_json = '$jsonSafe',
                            data_ultima_consulta = NOW()
                        WHERE id_rec = '$idRecSafe'";
            DBExecute($link, $qUpdate);
        }

        return [
            'success' => true,
            'idRec' => $idRec,
            'status' => 'CANCELADA',
            'data' => $responseInter
        ];
    }

    /**
     * Cancela uma cobrança individual de fatura emitida (PATCH /cobr/{txid}).
     * 
     * @param string $txid Identificador da cobrança
     * @param int $idFatura ID da fatura vinculada
     * @param mysqli $link Conexão ativa com o banco
     * @return array Resposta da operação
     */
    public static function cancelarCobrancaIndividualFaturaService($txid, $idFatura, $link)
    {
        global $ambienteConfig, $sslCertFile, $sslKeyFile, $caInfoFile;

        $txid = trim($txid);
        if (empty($txid)) {
            throw new Exception("TXID da cobrança é obrigatório.");
        }

        $token = getInterAccessToken($ambienteConfig, $sslCertFile, $sslKeyFile, $caInfoFile);
        $responseInter = cancelarCobrancaIndividual($ambienteConfig, $sslCertFile, $sslKeyFile, $caInfoFile, $token, $txid);

        $txidSafe = mysqli_real_escape_string($link, $txid);
        $qUpdatePag = "UPDATE Pagamentos 
                       SET status_pagamento = 'Cancelado', 
                           observacao = CONCAT(COALESCE(observacao,''), ' [Débito Automático Cancelado no Banco Inter]')
                       WHERE txid = '$txidSafe'";
        DBExecute($link, $qUpdatePag);

        return [
            'success' => true,
            'txid' => $txid,
            'idFatura' => $idFatura,
            'status' => 'CANCELADA',
            'data' => $responseInter
        ];
    }

    /**
     * Processa notificações passivas recebidas do Banco Inter via Webhook.
     * Payload esperado: {"recs": [ {"idRec": "...", "status": "APROVADA", ...} ]}
     * 
     * @param array|string $payload Payload JSON recebido
     * @param mysqli $link Conexão ativa com o banco
     * @return array Resumo do processamento
     */
    public static function tratarWebhookRecorrencia($payload, $link)
    {
        if (is_string($payload)) {
            $payload = json_decode($payload, true);
        }

        if (!is_array($payload) || empty($payload['recs'])) {
            return ['success' => false, 'message' => 'Nenhuma recorrência encontrada no payload.'];
        }

        $processados = 0;
        $atualizados = [];

        foreach ($payload['recs'] as $rec) {
            $idRec = $rec['idRec'] ?? null;
            $status = strtoupper($rec['status'] ?? '');

            if ($idRec && $status) {
                $idRecSafe = mysqli_real_escape_string($link, $idRec);
                $statusSafe = mysqli_real_escape_string($link, $status);
                $jsonSafe = mysqli_real_escape_string($link, json_encode($rec, JSON_UNESCAPED_UNICODE));

                $dataAceiteSql = ($status === 'APROVADA') ? "data_aceite = COALESCE(data_aceite, NOW())," : "";

                $q = "UPDATE PixRecorrencias 
                      SET status = '$statusSafe',
                          $dataAceiteSql
                          dados_bancarios_json = '$jsonSafe',
                          data_ultima_consulta = NOW()
                      WHERE id_rec = '$idRecSafe'";
                DBExecute($link, $q);

                $processados++;
                $atualizados[] = ['idRec' => $idRec, 'status' => $status];
            }
        }

        return [
            'success' => true,
            'processados' => $processados,
            'atualizados' => $atualizados
        ];
    }

    /**
     * Higienização preventiva executada pelo Cron Diário:
     * Varre contratos que foram inativados/cancelados no Dinovatech mas ainda constam com
     * PixRecorrencias ativa (APROVADA) no Banco Inter, cancelando-os via PATCH /rec/{idRec}.
     * 
     * @param mysqli $link Conexão ativa com o banco
     * @return array Resumo das higienizações realizadas
     */
    public static function higienizarContratosCancelados($link)
    {
        $hoje = date('Y-m-d');
        // Busca contratos que foram encerrados ou cuja data_fim_cobranca já expirou
        $qContratosInativos = "SELECT P.id_pix_recorrencia, P.id_rec, P.id_recorrencia, R.id_cliente, R.data_fim_cobranca
                               FROM PixRecorrencias P
                               JOIN Recorrencias R ON P.id_recorrencia = R.id_recorrencia
                               WHERE P.status = 'APROVADA'
                                 AND (
                                     (R.data_fim_cobranca IS NOT NULL AND R.data_fim_cobranca < '$hoje')
                                 )";
        $res = DBExecute($link, $qContratosInativos);

        $cancelados = [];
        $erros = [];

        if ($res && mysqli_num_rows($res) > 0) {
            while ($row = mysqli_fetch_assoc($res)) {
                $idRec = $row['id_rec'];
                try {
                    self::cancelarRecorrenciaContratoService($idRec, 'Higienização preventiva do Cron (Contrato expirado/inativado)', $link);
                    $cancelados[] = $idRec;
                } catch (Exception $e) {
                    $erros[] = "Erro ao cancelar idRec {$idRec}: " . $e->getMessage();
                }
            }
        }

        return [
            'total_verificados' => ($res ? mysqli_num_rows($res) : 0),
            'total_cancelados' => count($cancelados),
            'cancelados' => $cancelados,
            'erros' => $erros
        ];
    }
}
