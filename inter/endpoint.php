<?php
header('Content-Type: application/json');

require_once 'api.php';
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
            if (!$idFatura)
                throw new Exception("ID da Fatura é obrigatório.");
            $idFatura_safe = mysqli_real_escape_string($link, $idFatura);

            // 1. Verifica todos os pagamentos pendentes
            $queryCheck = "SELECT id_pagamento, txid, cod_qrcode, calendario FROM Pagamentos 
					  WHERE id_fatura = '{$idFatura_safe}' AND status_pagamento = 'Pendente'";
            $resultCheck = DBExecute($link, $queryCheck);

            $now = time();
            $validPaymentFound = false;
            $expiredPayments = [];


            while ($payment = mysqli_fetch_assoc($resultCheck)) {
                if (!empty($payment['txid'])) {
                    $calendario = json_decode($payment['calendario'], true);

                    if (isset($calendario['criacao']) && isset($calendario['expiracao'])) {
                        $criacao = new DateTime($calendario['criacao']);
                        $expiracaoTimestamp = $criacao->getTimestamp() + $calendario['expiracao'];

                        if ($expiracaoTimestamp > $now) {
                            // Pagamento válido e não expirado encontrado
                            $pixResponse = [
                                "txid" => $payment['txid'],
                                "pixCopiaECola" => $payment['cod_qrcode'],
                                "calendario" => $calendario
                            ];
                            $validPaymentFound = true;
                            break; // Usa o primeiro pagamento válido encontrado
                        } else {
                            // ** NOVA LÓGICA DE VERIFICAÇÃO FINAL **
                            // Antes de marcar como expirado, verifica na API se não foi pago milissegundos atrás
                            try {
                                $pixStatus = consultarPix($ambienteConfig, $sslCertFile, $sslKeyFile, $caInfoFile, $token, $payment['txid']);

                                if (isset($pixStatus->status) && $pixStatus->status === 'CONCLUIDA') {
                                    // Foi pago! Recuperar o pagamento
                                    $e2eid = $pixStatus->pix[0]->endToEndId ?? 'N/A';
                                    $observacao = "Recuperado pelo fluxo de expiração - E2EID: {$e2eid}";

                                    $e2eid_safe = mysqli_real_escape_string($link, $e2eid);
                                    $observacao_safe = mysqli_real_escape_string($link, $observacao);
                                    $idPagamento = $payment['id_pagamento'];

                                    // Atualiza para Confirmado
                                    $queryConfirm = "UPDATE Pagamentos SET status_pagamento = 'Confirmado', e2eid = '{$e2eid_safe}', observacao = '{$observacao_safe}' WHERE id_pagamento = '{$idPagamento}'";
                                    DBExecute($link, $queryConfirm);

                                    // Atualiza Fatura (Lógica simplificada, idealmente deveria ser funçao reutilizavel)
                                    $queryUpdateFatura = "UPDATE Faturas SET status = 'Liquidada' WHERE id_fatura = '{$idFatura_safe}'";
                                    DBExecute($link, $queryUpdateFatura);

                                    // Retorna sucesso de pagamento encontrado
                                    echo json_encode([
                                        'success' => true,
                                        'ConstavaNoBanco' => true,
                                        'data' => [
                                            "status" => "CONCLUIDA",
                                            "txid" => $payment['txid']
                                        ]
                                    ]);
                                    exit; // Encerra execução pois já achamos o pagamento

                                } else {
                                    // Realmente não foi pago ou está em outro status não final
                                    $expiredPayments[] = $payment['id_pagamento'];
                                }
                            } catch (Exception $e) {
                                // Se der erro na api, assume expirado para não travar o cliente
                                $expiredPayments[] = $payment['id_pagamento'];
                            }
                        }
                    }
                }
            }

            // Atualiza todos os pagamentos CONFIRMADOS como expirados
            if (!empty($expiredPayments)) {
                $ids = implode(",", array_map('intval', $expiredPayments));
                $queryUpdate = "UPDATE Pagamentos SET status_pagamento = 'Expirado' 
							WHERE id_pagamento IN ({$ids})";
                DBExecute($link, $queryUpdate);
            }

            // Se encontrou um pagamento válido, retorna
            if ($validPaymentFound) {
                echo json_encode(['success' => true, 'ConstavaNoBanco' => true, 'data' => $pixResponse]);
                break;
            }

            // ** LÓGICA REORDENADA **

            // 2. Busca os dados da fatura e itens para montar o payload
            $queryFatura = "SELECT f.valor_total_fatura, f.data_vencimento, c.nome as nome_cliente, c.cpf_cnpj FROM Faturas f JOIN Clientes c ON f.id_cliente = c.id_cliente WHERE f.id_fatura = '{$idFatura_safe}'";
            $resultFatura = DBExecute($link, $queryFatura);
            $fatura = mysqli_fetch_assoc($resultFatura);
            if (!$fatura)
                throw new Exception("Fatura não encontrada.");

            $queryItens = "SELECT s.nome_servico, i.tag FROM ItensFatura i JOIN Servicos s ON i.id_servico = s.id_servico WHERE i.id_fatura = '{$idFatura_safe}'";
            $resultItens = DBExecute($link, $queryItens);
            $itensDesc = [];
            while ($item = mysqli_fetch_assoc($resultItens)) {
                $itensDesc[] = $item['tag'] ?: $item['nome_servico'];
            }
            $itensString = implode('; ', $itensDesc);

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

            $calcTotals = AppHelper::calculateFaturaTotals($link, $idFatura);
            $valorCobranca = $calcTotals['valor_liquido'];

            $dadosCobranca = [
                'devedor' => $devedorPayload,
                'valorOriginal' => number_format($valorCobranca, 2, '.', ''),
                'chavePix' => $ambienteConfig['chave_pix'],
                'solicitacaoPagador' => "Pagamento Fatura " . $idFatura,
                'infoAdicionais' => $infoAdicionais
            ];

            // 3. Chama a API do Inter PRIMEIRO
            $pixResponse = newInstantPix($ambienteConfig, $sslCertFile, $sslKeyFile, $caInfoFile, $token, $dadosCobranca);

            // 4. Se a API retornou sucesso, INSERE o registro no banco
            if ($pixResponse && isset($pixResponse->txid)) {
                $valor_safe = mysqli_real_escape_string($link, $fatura['valor_total_fatura']);
                $txid_safe = mysqli_real_escape_string($link, $pixResponse->txid);
                $qrcode_safe = mysqli_real_escape_string($link, $pixResponse->pixCopiaECola);
                $calendario_safe = mysqli_real_escape_string($link, json_encode($pixResponse->calendario));

                $queryInsert = "INSERT INTO Pagamentos (id_fatura, valor_pago, data_pagamento, status_pagamento, txid, cod_qrcode, calendario) 
                                VALUES ('{$idFatura_safe}', '{$valor_safe}', CURDATE(), 'Pendente', '{$txid_safe}', '{$qrcode_safe}', '{$calendario_safe}')";

                DBExecute($link, $queryInsert);
            } else {
                throw new Exception("Falha ao obter resposta da API do Inter ou txid não encontrado.");
            }

            // 5. Retorna os dados para o frontend
            echo json_encode(['success' => true, 'ConstavaNoBanco' => false, 'data' => $pixResponse]);
            break;

        case 'verificar_pagamento_pix':
            $txid = $_GET['txid'] ?? null;
            if (!$txid)
                throw new Exception("TXID é obrigatório.");

            $pixStatus = consultarPix($ambienteConfig, $sslCertFile, $sslKeyFile, $caInfoFile, $token, $txid);

            if ($pixStatus->status === 'CONCLUIDA' && !empty($pixStatus->pix)) {
                $e2eid = $pixStatus->pix[0]->endToEndId;
                $observacao = "E2EID: {$e2eid} - TXID: {$txid}";
                $e2eid_safe = mysqli_real_escape_string($link, $e2eid);
                $txid_safe = mysqli_real_escape_string($link, $txid);
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

            echo json_encode(['success' => true, 'data' => ['status' => $pixStatus->status], 'audit' => $pixStatus]);
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
