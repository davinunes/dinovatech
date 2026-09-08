<?php
header('Content-Type: application/json');

require_once 'api.php';
require_once 'PixAutomaticoService.php';
require_once '../database.php';
require_once '../dinovatech/helpers/AppHelper.php';

$action = $_GET['action'] ?? null;
$requestBody = json_decode(file_get_contents('php://input'), true);

$link = DBConnect();

try {
    if (!$link)
        throw new Exception("Falha na conexão com o banco de dados.");

    global $ambienteConfig, $sslCertFile, $sslKeyFile, $caInfoFile;
    $token = getInterAccessToken($ambienteConfig, $sslCertFile, $sslKeyFile, $caInfoFile);

    switch ($action) {
        case 'obter_ou_criar_pix_pagamento':
            $idFatura = $requestBody['id_fatura'] ?? null;
            $valorPagamento = $requestBody['valor_pagamento'] ?? null; // Novo: Valor opcional para pagamento parcial

            if (!$idFatura)
                throw new Exception("ID da Fatura é obrigatório.");
            $idFatura_safe = mysqli_real_escape_string($link, $idFatura);

            // Obtém dados da Fatura
            $queryFatura = "SELECT f.valor_total_fatura, f.data_vencimento, f.permitir_pagamento_parcial, c.nome as nome_cliente, c.cpf_cnpj 
                            FROM Faturas f JOIN Clientes c ON f.id_cliente = c.id_cliente 
                            WHERE f.id_fatura = '{$idFatura_safe}'";
            $resultFatura = DBExecute($link, $queryFatura);
            $fatura = mysqli_fetch_assoc($resultFatura);
            if (!$fatura)
                throw new Exception("Fatura não encontrada.");

            // Calcula Valor a Ser Cobrado
            $calcTotals = AppHelper::calculateFaturaTotals($link, $idFatura);
            // $valorOriginal = $calcTotals['valor_liquido']; // Valor total restante

            // Determina o valor da cobrança
            if ($valorPagamento && is_numeric($valorPagamento) && $valorPagamento > 0) {
                // Se foi enviado um valor customizado, verifica se é permitido
                if ($fatura['permitir_pagamento_parcial'] == '1') {
                    $valorCobranca = (float) $valorPagamento;
                } else {
                    throw new Exception("Pagamento parcial não habilitado para esta fatura.");
                }
            } else {
                // Caso contrário, usa o valor líquido TOTAL (considerando descontos já aplicados no helper)
                // MAS devemos subtrair o que já foi pago se for um pagamento complementar de uma fatura parcial?
                // O AppHelper calculateFaturaTotals retorna o valor TOTAL LIQUIDO da fatura, não o saldo devedor.
                // Precisamos calcular o saldo devedor aqui se não for passado valor explicito.

                $queryPago = "SELECT SUM(valor_pago) as total_pago FROM Pagamentos WHERE id_fatura = '{$idFatura_safe}' AND status_pagamento = 'Confirmado'";
                $resPago = DBExecute($link, $queryPago);
                $rowPago = mysqli_fetch_assoc($resPago);
                $totalPago = $rowPago['total_pago'] ?? 0.00;

                $valorCobranca = $calcTotals['valor_liquido'] - $totalPago;
                if ($valorCobranca <= 0) {
                    throw new Exception("Fatura já está liquidada.");
                }
            }

            // 1. Verifica pagamentos pendentes
            $queryCheck = "SELECT id_pagamento, txid, cod_qrcode, calendario, valor_pago FROM Pagamentos 
					  WHERE id_fatura = '{$idFatura_safe}' AND status_pagamento = 'Pendente'";
            $resultCheck = DBExecute($link, $queryCheck);

            $now = time();
            $validPaymentFound = false;
            $expiredPayments = [];


            while ($payment = mysqli_fetch_assoc($resultCheck)) {
                if (!empty($payment['txid'])) {

                    // VERIFICAÇÃO DE VALOR (User Request)
                    $valorPendente = (float) $payment['valor_pago'];
                    // Compara com tolerância de centavos
                    if (abs($valorPendente - $valorCobranca) > 0.01) {
                        // Valor diferente! Precisamos verificar esse PIX e expirar se não foi pago.
                        try {
                            $pixStatus = consultarPix($ambienteConfig, $sslCertFile, $sslKeyFile, $caInfoFile, $token, $payment['txid']);

                            if (isset($pixStatus->status) && $pixStatus->status === 'CONCLUIDA') {
                                // Foi pago! (Mesmo com valor diferente, improvável mas possível)
                                // Confirma e encerra (Lógica de confirmação duplicada abaixo, poderia refatorar)
                                $e2eid = $pixStatus->pix[0]->endToEndId ?? 'N/A';
                                $observacao = "Recuperado (Valor Divergente) - E2EID: {$e2eid}";
                                $e2eid_safe = mysqli_real_escape_string($link, $e2eid);
                                $observacao_safe = mysqli_real_escape_string($link, $observacao);
                                $idPagamento = $payment['id_pagamento'];

                                DBExecute($link, "UPDATE Pagamentos SET status_pagamento = 'Confirmado', e2eid = '{$e2eid_safe}', observacao = '{$observacao_safe}' WHERE id_pagamento = '{$idPagamento}'");

                                // Atualiza status da fatura se quitada (Simplificado)
                                // Idealmente recalcularia saldo

                                echo json_encode([
                                    'success' => true,
                                    'ConstavaNoBanco' => true, // Tecnicamente sim
                                    'data' => ["status" => "CONCLUIDA", "txid" => $payment['txid']]
                                ]);
                                exit;

                            } else {
                                // Não foi pago. Expira este PIX pois o valor desejado mudou.
                                $expiredPayments[] = $payment['id_pagamento'];
                                // Tenta baixar/cancelar na API do Inter também?
                                // Por enquanto foca em marcar 'Expirado' no banco para não ser reutilizado.
                            }
                        } catch (Exception $e) {
                            // Se der erro, expira por segurança
                            $expiredPayments[] = $payment['id_pagamento'];
                        }
                        continue; // Vai para o próximo (ou gera novo)
                    }

                    // Se valor bate, verifica validade de tempo
                    $calendario = json_decode($payment['calendario'], true);

                    if (isset($calendario['criacao']) && isset($calendario['expiracao'])) {
                        $criacao = new DateTime($calendario['criacao']);
                        $expiracaoTimestamp = $criacao->getTimestamp() + $calendario['expiracao'];

                        if ($expiracaoTimestamp > $now) {
                            // PIX Válido e com mesmo valor! Reutiliza.
                            $pixResponse = [
                                "txid" => $payment['txid'],
                                "pixCopiaECola" => $payment['cod_qrcode'],
                                "calendario" => $calendario
                            ];
                            $validPaymentFound = true;
                            break;
                        } else {
                            // Expirado por tempo. Verifica status final.
                            try {
                                $pixStatus = consultarPix($ambienteConfig, $sslCertFile, $sslKeyFile, $caInfoFile, $token, $payment['txid']);

                                if (isset($pixStatus->status) && $pixStatus->status === 'CONCLUIDA') {
                                    // Pago! Recupera.
                                    $e2eid = $pixStatus->pix[0]->endToEndId ?? 'N/A';
                                    $observacao = "Recuperado por expiração - E2EID: {$e2eid}";
                                    $e2eid_safe = mysqli_real_escape_string($link, $e2eid);
                                    $observacao_safe = mysqli_real_escape_string($link, $observacao);
                                    $idPagamento = $payment['id_pagamento'];

                                    DBExecute($link, "UPDATE Pagamentos SET status_pagamento = 'Confirmado', e2eid = '{$e2eid_safe}', observacao = '{$observacao_safe}' WHERE id_pagamento = '{$idPagamento}'");

                                    // Verifica Liq
                                    $totalPagoBD = $totalPago ?? 0; // Aproximação, idealmente refetch
                                    if (($totalPagoBD + $valorPendente) >= $calcTotals['valor_liquido']) {
                                        DBExecute($link, "UPDATE Faturas SET status = 'Liquidada' WHERE id_fatura = '{$idFatura_safe}'");
                                    }

                                    echo json_encode([
                                        'success' => true,
                                        'ConstavaNoBanco' => true,
                                        'data' => ["status" => "CONCLUIDA", "txid" => $payment['txid']]
                                    ]);
                                    exit;

                                } else {
                                    $expiredPayments[] = $payment['id_pagamento'];
                                }
                            } catch (Exception $e) {
                                $expiredPayments[] = $payment['id_pagamento'];
                            }
                        }
                    }
                }
            }

            // Expira pagamentos inválidos/divergentes
            if (!empty($expiredPayments)) {
                $ids = implode(",", array_map('intval', $expiredPayments));
                $queryUpdate = "UPDATE Pagamentos SET status_pagamento = 'Expirado' WHERE id_pagamento IN ({$ids})";
                DBExecute($link, $queryUpdate);
            }

            if ($validPaymentFound) {
                echo json_encode(['success' => true, 'ConstavaNoBanco' => true, 'data' => $pixResponse]);
                break;
            }

            // GERA NOVO PIX
            // Reutiliza queryItens e dados do payload...

            $queryItens = "SELECT s.nome_servico, i.tag FROM ItensFatura i JOIN Servicos s ON i.id_servico = s.id_servico WHERE i.id_fatura = '{$idFatura_safe}'";
            $resultItens = DBExecute($link, $queryItens);
            $itensDesc = [];
            while ($item = mysqli_fetch_assoc($resultItens)) {
                $itensDesc[] = $item['tag'] ?: $item['nome_servico'];
            }
            $itensString = implode('; ', $itensDesc);
            if ($valorPagamento) {
                $itensString .= " (Pagamento Parcial)";
            }

            $docLimpo = preg_replace('/[^0-9]/', '', $fatura['cpf_cnpj']);
            $devedorPayload = ['nome' => $fatura['nome_cliente']];
            if (strlen($docLimpo) == 11) {
                $devedorPayload['cpf'] = $docLimpo;
            } elseif (strlen($docLimpo) == 14) {
                $devedorPayload['cnpj'] = $docLimpo;
            }

            $demonstrativo = iconv('UTF-8', 'ASCII//TRANSLIT', $itensString);
            $demonstrativo = preg_replace('/[^\w\s\-\.;,]/', '', $demonstrativo);
            $demonstrativo = substr($demonstrativo, 0, 200);

            $infoAdicionais = [
                ["nome" => "CodigoFatura", "valor" => (string) $idFatura],
                ["nome" => "Vencimento", "valor" => date("d/m/Y", strtotime($fatura['data_vencimento']))],
                ["nome" => "demonstrativo", "valor" => $demonstrativo]
            ];

            // $valorCobranca já foi calculado acima

            $dadosCobranca = [
                'devedor' => $devedorPayload,
                'valorOriginal' => number_format($valorCobranca, 2, '.', ''),
                'chavePix' => $ambienteConfig['chave_pix'],
                'solicitacaoPagador' => "Pagamento Fatura " . $idFatura,
                'infoAdicionais' => $infoAdicionais
            ];

            $pixResponse = newInstantPix($ambienteConfig, $sslCertFile, $sslKeyFile, $caInfoFile, $token, $dadosCobranca);

            if ($pixResponse && isset($pixResponse->txid)) {
                $valor_safe = mysqli_real_escape_string($link, $valorCobranca); // Salva o valor exato cobrado
                $txid_safe = mysqli_real_escape_string($link, $pixResponse->txid);
                $qrcode_safe = mysqli_real_escape_string($link, $pixResponse->pixCopiaECola);
                $calendario_safe = mysqli_real_escape_string($link, json_encode($pixResponse->calendario));

                $queryInsert = "INSERT INTO Pagamentos (id_fatura, valor_pago, data_pagamento, status_pagamento, txid, cod_qrcode, calendario) 
                                VALUES ('{$idFatura_safe}', '{$valor_safe}', CURDATE(), 'Pendente', '{$txid_safe}', '{$qrcode_safe}', '{$calendario_safe}')";

                DBExecute($link, $queryInsert);
            } else {
                throw new Exception("Falha ao obter resposta da API do Inter ou txid não encontrado.");
            }

            echo json_encode(['success' => true, 'ConstavaNoBanco' => false, 'data' => $pixResponse]);
            break;

        case 'verificar_pagamento_pix':
            $txid = $_GET['txid'] ?? null;
            if (!$txid)
                throw new Exception("TXID é obrigatório.");

            $txid_safe = mysqli_real_escape_string($link, $txid);

            // Detecta se é CobV (Jornada 4 / Pix Automático) verificando se existe PixRecorrencias com esse txid_inicial
            $qTipoCob = "SELECT P.id_pix_recorrencia FROM PixRecorrencias P WHERE P.txid_inicial = '$txid_safe' LIMIT 1";
            $resTipoCob = DBExecute($link, $qTipoCob);
            $isCobV = ($resTipoCob && mysqli_num_rows($resTipoCob) > 0);

            if ($isCobV) {
                // Jornada 4: consulta via GET /cobv/{txid}
                $pixStatus = consultarCobv($ambienteConfig, $sslCertFile, $sslKeyFile, $caInfoFile, $token, $txid);
                $statusPix = $pixStatus->status ?? 'ATIVA';
                $pixArr = $pixStatus->pix ?? null;
                // CobV retorna pix como objeto único, não array
                if ($pixArr && !is_array($pixArr)) {
                    $pixArr = [$pixArr];
                }
            } else {
                // Pix imediato: consulta via GET /cob/{txid}
                $pixStatus = consultarPix($ambienteConfig, $sslCertFile, $sslKeyFile, $caInfoFile, $token, $txid);
                $statusPix = $pixStatus->status ?? 'ATIVA';
                $pixArr = !empty($pixStatus->pix) ? (array) $pixStatus->pix : null;
            }

            if ($statusPix === 'CONCLUIDA' && !empty($pixArr)) {
                $e2eid = $pixArr[0]->endToEndId ?? '';
                $observacao = "E2EID: {$e2eid} - TXID: {$txid}";
                $e2eid_safe = mysqli_real_escape_string($link, $e2eid);
                $observacao_safe = mysqli_real_escape_string($link, $observacao);

                $queryUpdatePagamento = "UPDATE Pagamentos SET status_pagamento = 'Confirmado', e2eid = '{$e2eid_safe}', observacao = '{$observacao_safe}' WHERE txid = '{$txid_safe}' AND status_pagamento = 'Pendente'";
                DBExecute($link, $queryUpdatePagamento);

                if (mysqli_affected_rows($link) > 0) {
                    $queryGetFatura = "SELECT id_fatura FROM Pagamentos WHERE txid = '{$txid_safe}'";
                    $resultFaturaId = DBExecute($link, $queryGetFatura);
                    $faturaData = mysqli_fetch_assoc($resultFaturaId);
                    $idFatura_safe = $faturaData['id_fatura'];

                    $queryValorFatura = "SELECT valor_total_fatura FROM Faturas WHERE id_fatura = '{$idFatura_safe}'";
                    $resultValorFatura = DBExecute($link, $queryValorFatura);
                    $fatura = mysqli_fetch_assoc($resultValorFatura);
                    $valorTotalFatura = $fatura['valor_total_fatura'];

                    $querySomaPagamentos = "SELECT SUM(valor_pago) as total_pago FROM Pagamentos WHERE id_fatura = '{$idFatura_safe}' AND status_pagamento = 'Confirmado'";
                    $resultSoma = DBExecute($link, $querySomaPagamentos);
                    $soma = mysqli_fetch_assoc($resultSoma);
                    $totalPago = $soma['total_pago'];

                    if ($totalPago >= $valorTotalFatura) {
                        $queryUpdateFatura = "UPDATE Faturas SET status = 'Liquidada' WHERE id_fatura = '{$idFatura_safe}'";
                        DBExecute($link, $queryUpdateFatura);
                    }
                }
            }

            echo json_encode(['success' => true, 'data' => ['status' => $statusPix], 'audit' => $pixStatus]);
            break;

        case 'consultar_extrato_completo':
            $dataInicio = $_GET['dataInicio'] ?? $requestBody['dataInicio'] ?? null;
            $dataFim = $_GET['dataFim'] ?? $requestBody['dataFim'] ?? null;

            if (!$dataInicio || !$dataFim) {
                throw new Exception("Parâmetros dataInicio e dataFim (YYYY-MM-DD) são obrigatórios.");
            }

            $params = [
                'dataInicio' => $dataInicio,
                'dataFim' => $dataFim
            ];

            // Parâmetros opcionais de paginação tradicional
            if (isset($_GET['pagina'])) $params['pagina'] = (int)$_GET['pagina'];
            if (isset($_GET['tamanhoPagina'])) $params['tamanhoPagina'] = (int)$_GET['tamanhoPagina'];
            if (isset($_GET['tipoOperacao'])) $params['tipoOperacao'] = $_GET['tipoOperacao'];
            if (isset($_GET['tipoTransacao'])) $params['tipoTransacao'] = $_GET['tipoTransacao'];

            // Parâmetros opcionais de paginação por scroll
            if (isset($_GET['scrollEnabled'])) $params['scrollEnabled'] = filter_var($_GET['scrollEnabled'], FILTER_VALIDATE_BOOLEAN);
            if (isset($_GET['scrollId'])) $params['scrollId'] = $_GET['scrollId'];

            $extrato = consultarExtratoCompleto($ambienteConfig, $sslCertFile, $sslKeyFile, $caInfoFile, $token, $params);
            echo json_encode(['success' => true, 'data' => $extrato]);
            break;

        case 'exportar_extrato_pdf':
            $dataInicio = $_GET['dataInicio'] ?? $requestBody['dataInicio'] ?? null;
            $dataFim = $_GET['dataFim'] ?? $requestBody['dataFim'] ?? null;

            if (!$dataInicio || !$dataFim) {
                throw new Exception("Parâmetros dataInicio e dataFim (YYYY-MM-DD) são obrigatórios.");
            }

            $pdfResponse = exportarExtratoPdf($ambienteConfig, $sslCertFile, $sslKeyFile, $caInfoFile, $token, $dataInicio, $dataFim);

            // A API do Inter retorna JSON contendo {"pdf": "base64..."}
            $rawPdf = null;
            $decodedJson = json_decode($pdfResponse, true);

            if (is_array($decodedJson) && !empty($decodedJson['pdf'])) {
                $rawPdf = base64_decode($decodedJson['pdf']);
            } elseif (is_string($pdfResponse) && strpos($pdfResponse, '%PDF') === 0) {
                $rawPdf = $pdfResponse;
            } else {
                $checkB64 = base64_decode($pdfResponse, true);
                if ($checkB64 && strpos($checkB64, '%PDF') !== false) {
                    $rawPdf = $checkB64;
                } else {
                    $rawPdf = $pdfResponse;
                }
            }

            // Se solicitado download direto via GET
            if (isset($_GET['download']) && $_GET['download'] == '1') {
                header('Content-Type: application/pdf');
                header('Content-Disposition: attachment; filename="extrato_inter_' . $dataInicio . '_' . $dataFim . '.pdf"');
                header('Content-Length: ' . strlen($rawPdf));
                echo $rawPdf;
                exit;
            }

            echo json_encode(['success' => true, 'data' => base64_encode($rawPdf)]);
            break;

        case 'obter_ou_criar_pix_jornada4':
            $idFatura = $requestBody['id_fatura'] ?? $_GET['id_fatura'] ?? null;
            if (!$idFatura) {
                throw new Exception("ID da fatura é obrigatório.");
            }

            $idFaturaSafe = (int) $idFatura;

            // 1. Verifica se já existe um PixRecorrencias pendente ou aprovado com pagamento válido para esta fatura
            $qCheck = "SELECT P.*, Pag.id_pagamento, Pag.txid, Pag.cod_qrcode, Pag.calendario, Pag.status_pagamento
                       FROM PixRecorrencias P
                       LEFT JOIN Pagamentos Pag ON P.txid_inicial = Pag.txid
                       WHERE P.id_fatura_inicial = $idFaturaSafe AND Pag.status_pagamento = 'Pendente'
                       ORDER BY P.id_pix_recorrencia DESC LIMIT 1";
            $resCheck = DBExecute($link, $qCheck);

            if ($resCheck && mysqli_num_rows($resCheck) > 0) {
                $existente = mysqli_fetch_assoc($resCheck);
                $cal = json_decode($existente['calendario'] ?? '', true);
                $valido = false;

                if (!empty($cal['criacao']) && !empty($cal['expiracao'])) {
                    $dtCriacao = new DateTime($cal['criacao']);
                    $expiracao = $dtCriacao->getTimestamp() + (int) $cal['expiracao'];
                    if ($expiracao > time()) {
                        $valido = true;
                    }
                }

                if ($valido && !empty($existente['cod_qrcode'])) {
                    echo json_encode([
                        'success' => true,
                        'reutilizado' => true,
                        'idRec' => $existente['id_rec'],
                        'txid' => $existente['txid'],
                        'pixCopiaECola' => $existente['cod_qrcode'],
                        'calendario' => $cal,
                        'valorRecorrente' => $existente['valor_recorrente'],
                        'status' => $existente['status']
                    ]);
                    break;
                }
            }

            // Gera novo fluxo Jornada 4 (Passos 1 a 4)
            $resultadoJornada4 = PixAutomaticoService::gerarJornada4ParaFatura($idFaturaSafe, $link);
            echo json_encode($resultadoJornada4);
            break;

        case 'consultar_status_recorrencia':
            $idRec = $_GET['idRec'] ?? $requestBody['idRec'] ?? null;
            $idFatura = $_GET['id_fatura'] ?? $requestBody['id_fatura'] ?? null;

            if (empty($idRec) && !empty($idFatura)) {
                $idFaturaSafe = (int) $idFatura;
                $qBuscaRec = "SELECT P.id_rec 
                              FROM PixRecorrencias P
                              JOIN ItensFatura I ON P.id_recorrencia = I.id_recorrencia
                              WHERE I.id_fatura = $idFaturaSafe OR P.id_fatura_inicial = $idFaturaSafe
                              ORDER BY P.id_pix_recorrencia DESC LIMIT 1";
                $resBuscaRec = DBExecute($link, $qBuscaRec);
                if ($resBuscaRec && mysqli_num_rows($resBuscaRec) > 0) {
                    $rowRec = mysqli_fetch_assoc($resBuscaRec);
                    $idRec = $rowRec['id_rec'];
                }
            }

            if (empty($idRec)) {
                throw new Exception("Identificador idRec ou id_fatura não encontrado.");
            }

            $resultadoSync = PixAutomaticoService::sincronizarStatusRecorrencia($idRec, $link);
            echo json_encode($resultadoSync);
            break;

        case 'cancelar_pix_recorrencia_contrato':
            $idRec = $_POST['idRec'] ?? $requestBody['idRec'] ?? $_GET['idRec'] ?? null;
            $motivo = $_POST['motivo'] ?? $requestBody['motivo'] ?? 'Cancelamento solicitado pelo administrador';

            if (empty($idRec)) {
                $idRecorrencia = (int)($_POST['id_recorrencia'] ?? $requestBody['id_recorrencia'] ?? 0);
                if ($idRecorrencia > 0) {
                    $qRec = "SELECT id_rec FROM PixRecorrencias WHERE id_recorrencia = $idRecorrencia AND status != 'CANCELADA' LIMIT 1";
                    $rRec = DBExecute($link, $qRec);
                    if ($rRec && mysqli_num_rows($rRec) > 0) {
                        $idRec = mysqli_fetch_assoc($rRec)['id_rec'];
                    }
                }
            }

            if (empty($idRec)) {
                throw new Exception("Identificador idRec da recorrência não informado.");
            }

            $resCancel = PixAutomaticoService::cancelarRecorrenciaContratoService($idRec, $motivo, $link);
            echo json_encode($resCancel);
            break;

        case 'cancelar_pix_cobranca_individual':
            $txid = $_POST['txid'] ?? $requestBody['txid'] ?? $_GET['txid'] ?? null;
            $idFatura = (int)($_POST['id_fatura'] ?? $requestBody['id_fatura'] ?? $_GET['id_fatura'] ?? 0);

            if (empty($txid)) {
                throw new Exception("TXID da cobrança é obrigatório para cancelamento individual.");
            }

            $resCancelCob = PixAutomaticoService::cancelarCobrancaIndividualFaturaService($txid, $idFatura, $link);
            echo json_encode($resCancelCob);
            break;

        case 'obter_status_pix_recorrencia_fatura':
            $idFatura = (int)($_GET['id_fatura'] ?? $requestBody['id_fatura'] ?? 0);
            if ($idFatura <= 0) {
                throw new Exception("ID da fatura é obrigatório.");
            }

            $qInfo = "SELECT P.*, R.id_servico, S.nome_servico, Pag.status_pagamento as status_pagamento_fatura, Pag.txid as txid_fatura
                      FROM Faturas F
                      LEFT JOIN ItensFatura I ON F.id_fatura = I.id_fatura
                      LEFT JOIN Recorrencias R ON I.id_recorrencia = R.id_recorrencia
                      LEFT JOIN PixRecorrencias P ON (P.id_recorrencia = R.id_recorrencia OR P.id_fatura_inicial = F.id_fatura)
                      LEFT JOIN Servicos S ON R.id_servico = S.id_servico
                      LEFT JOIN Pagamentos Pag ON Pag.id_fatura = F.id_fatura AND Pag.status_pagamento = 'Pendente'
                      WHERE F.id_fatura = $idFatura
                      ORDER BY P.id_pix_recorrencia DESC LIMIT 1";
            $resInfo = DBExecute($link, $qInfo);
            $pixRecInfo = $resInfo ? mysqli_fetch_assoc($resInfo) : null;

            echo json_encode([
                'success' => true,
                'hasPixRecorrencia' => !empty($pixRecInfo['id_rec']),
                'data' => $pixRecInfo
            ]);
            break;

        case 'configurar_webhook_rec':
            $webhookUrl = $requestBody['webhookUrl'] ?? $_GET['webhookUrl'] ?? null;
            if (empty($webhookUrl)) {
                $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
                $host = $_SERVER['HTTP_HOST'] ?? 'app.dinovatech.com.br';
                $webhookUrl = "{$protocol}://{$host}/inter/webhook_rec.php";
            }

            $resWeb = configurarWebhookRecorrencia($ambienteConfig, $sslCertFile, $sslKeyFile, $caInfoFile, $token, $webhookUrl);
            echo json_encode(['success' => true, 'webhookUrl' => $webhookUrl, 'data' => $resWeb]);
            break;

        case 'limpar_token_cache':
            getInterAccessToken($ambienteConfig, $sslCertFile, $sslKeyFile, $caInfoFile, true);
            echo json_encode(['success' => true, 'message' => 'Cache de token do Banco Inter renovado com sucesso.']);
            break;

        default:
            throw new Exception("Ação inválida ou não especificada.");
    }

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
} finally {
    if ($link)
        DBClose($link);
}
