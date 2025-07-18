<?php
header('Content-Type: application/json');

require_once 'api.php';
require_once '../database.php';

$action = $_GET['action'] ?? null;
$requestBody = json_decode(file_get_contents('php://input'), true);

$link = DBConnect();

try {
    if (!$link) throw new Exception("Falha na conexão com o banco de dados.");

    global $ambienteConfig, $sslCertFile, $sslKeyFile, $caInfoFile;
    $token = getInterAccessToken($ambienteConfig, $sslCertFile, $sslKeyFile, $caInfoFile);

    switch ($action) {
        case 'obter_ou_criar_pix_pagamento':
            $idFatura = $requestBody['id_fatura'] ?? null;
            if (!$idFatura) throw new Exception("ID da Fatura é obrigatório.");
            $idFatura_safe = mysqli_real_escape_string($link, $idFatura);

            // 1. Verifica se já existe um pagamento pendente e válido
            $queryCheck = "SELECT txid, cod_qrcode, calendario FROM Pagamentos WHERE id_fatura = '{$idFatura_safe}' AND status_pagamento = 'Pendente'";
            $resultCheck = DBExecute($link, $queryCheck);
            $pendingPayment = mysqli_fetch_assoc($resultCheck);

            if ($pendingPayment && !empty($pendingPayment['txid'])) {
                $calendario = json_decode($pendingPayment['calendario']);
                if (isset($calendario->criacao) && isset($calendario->expiracao)) {
                    $criacao = new DateTime($calendario->criacao);
                    $expiracaoTimestamp = $criacao->getTimestamp() + $calendario->expiracao;
                    if ($expiracaoTimestamp > time()) {
                        $pixResponse = ["txid" => $pendingPayment['txid'], "pixCopiaECola" => $pendingPayment['cod_qrcode'], "calendario" => $calendario];
                        echo json_encode(['success' => true, 'data' => $pixResponse]);
                        break;
                    }
                }
            }

            // 2. Se não há PIX pendente válido, cria um novo
            $queryFatura = "SELECT f.valor_total_fatura, f.data_vencimento, c.nome as nome_cliente, c.cpf_cnpj FROM Faturas f JOIN Clientes c ON f.id_cliente = c.id_cliente WHERE f.id_fatura = '{$idFatura_safe}'";
            $resultFatura = DBExecute($link, $queryFatura);
            $fatura = mysqli_fetch_assoc($resultFatura);
            if (!$fatura) throw new Exception("Fatura não encontrada.");

            $queryItens = "SELECT s.nome_servico, i.tag FROM ItensFatura i JOIN Servicos s ON i.id_servico = s.id_servico WHERE i.id_fatura = '{$idFatura_safe}'";
            $resultItens = DBExecute($link, $queryItens);
            $itensDesc = [];
            while ($item = mysqli_fetch_assoc($resultItens)) {
                $itensDesc[] = $item['tag'] ?: $item['nome_servico'];
            }
            $itensString = implode('; ', $itensDesc);

            $valor = $fatura['valor_total_fatura'];
            $valor_safe = mysqli_real_escape_string($link, $valor);
            $queryInsert = "INSERT INTO Pagamentos (id_fatura, valor_pago, data_pagamento, status_pagamento) VALUES ('{$idFatura_safe}', '{$valor_safe}', CURDATE(), 'Pendente')";
            DBExecute($link, $queryInsert);
            $idPagamento = mysqli_insert_id($link);

            $docLimpo = preg_replace('/[^0-9]/', '', $fatura['cpf_cnpj']);
            $devedorPayload = ['nome' => $fatura['nome_cliente']];
            if (strlen($docLimpo) == 11) {
                $devedorPayload['cpf'] = $docLimpo;
            } elseif (strlen($docLimpo) == 14) {
                $devedorPayload['cnpj'] = $docLimpo;
            }

            $infoAdicionais = [
                ["nome" => "Fatura", "valor" => (string)$idFatura],
                ["nome" => "Vencimento", "valor" => date("d/m/Y", strtotime($fatura['data_vencimento']))],
                ["nome" => "Itens", "valor" => substr($itensString, 0, 140)]
            ];
            
            $dadosCobranca = [
                'devedor' => $devedorPayload,
                'valorOriginal' => number_format($valor, 2, '.', ''),
                'chavePix' => $ambienteConfig['chave_pix'],
                'solicitacaoPagador' => "Pagamento da Fatura #{$idFatura}",
                'infoAdicionais' => $infoAdicionais
            ];

            $pixResponse = newInstantPix($ambienteConfig, $sslCertFile, $sslKeyFile, $caInfoFile, $token, $dadosCobranca);

            $txid_safe = mysqli_real_escape_string($link, $pixResponse->txid);
            $qrcode_safe = mysqli_real_escape_string($link, $pixResponse->pixCopiaECola);
            $calendario_safe = mysqli_real_escape_string($link, json_encode($pixResponse->calendario));
            $queryUpdate = "UPDATE Pagamentos SET txid = '{$txid_safe}', cod_qrcode = '{$qrcode_safe}', calendario = '{$calendario_safe}' WHERE id_pagamento = {$idPagamento}";
            DBExecute($link, $queryUpdate);

            echo json_encode(['success' => true, 'data' => $pixResponse]);
            break;

        case 'verificar_pagamento_pix':
            $txid = $_GET['txid'] ?? null;
            if (!$txid) throw new Exception("TXID é obrigatório.");

            $pixStatus = consultarPix($ambienteConfig, $sslCertFile, $sslKeyFile, $caInfoFile, $token, $txid);

            if ($pixStatus->status === 'CONCLUIDA' && !empty($pixStatus->pix)) {
                $e2eid = $pixStatus->pix[0]->endToEndId;
                
                // ** NOVO: Cria a string de observação **
                $observacao = "Pago com pix - E2EID: {$e2eid} - TXID: {$txid}";

                $e2eid_safe = mysqli_real_escape_string($link, $e2eid);
                $txid_safe = mysqli_real_escape_string($link, $txid);
                $observacao_safe = mysqli_real_escape_string($link, $observacao);

                // ** ALTERADO: Atualiza o pagamento com a nova observação **
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
            
            echo json_encode(['success' => true, 'data' => ['status' => $pixStatus->status]]);
            break;
            
        default:
            throw new Exception("Ação inválida ou não especificada.");
    }

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
} finally {
    if ($link) DBClose($link);
}
