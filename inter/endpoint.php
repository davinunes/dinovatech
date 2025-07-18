<?php
header('Content-Type: application/json');
// Usa require_once para carregar as funções da API e a configuração
require_once 'api.php';

// Pega a ação da URL e decodifica o corpo da requisição se for POST/PUT
$action = $_GET['action'] ?? null;
$requestBody = json_decode(file_get_contents('php://input'), true);

try {
    // As variáveis de configuração e caminhos dos certificados vêm do api.php (que inclui o config.php)
    global $ambienteConfig, $sslCertFile, $sslKeyFile, $caInfoFile;

    // O token é obtido para todas as ações
    $token = getInterAccessToken($ambienteConfig, $sslCertFile, $sslKeyFile, $caInfoFile);

    switch ($action) {
        case 'criar_cobranca':
            $dadosCobranca = [
                'expiracaoSegundos' => 3600,
                'devedor' => ["cpf" => "12345678910", "nome" => "Cliente de Teste via Fetch"],
                'valorOriginal' => "1.50",
                'chavePix' => "7d9f0335-8dcc-4054-9bf9-0dbd61d36906",
                'solicitacaoPagador' => "Pagamento de teste via API."
            ];
            $resultado = newInstantPix($ambienteConfig, $sslCertFile, $sslKeyFile, $caInfoFile, $token, $dadosCobranca);
            echo json_encode(['success' => true, 'data' => $resultado]);
            break;

        case 'consultar_cobranca':
            $txid = $_GET['txid'] ?? null;
            if (!$txid) throw new Exception("TXID é obrigatório para a consulta.");
            $resultado = consultarPix($ambienteConfig, $sslCertFile, $sslKeyFile, $caInfoFile, $token, $txid);
            echo json_encode(['success' => true, 'data' => $resultado]);
            break;

        case 'pagar_cobranca':
            $txid = $requestBody['txid'] ?? null;
            $valor = $requestBody['valor'] ?? null;
            if (!$txid || !$valor) throw new Exception("TXID e Valor são obrigatórios para pagar.");
            
            $resultado = pagarPix($ambienteConfig, $token, $txid, $valor);
            echo json_encode(['success' => true, 'data' => $resultado]);
            break;

        case 'consultar_recibo':
            $e2eid = $_GET['e2eid'] ?? null;
            if (!$e2eid) throw new Exception("E2EID é obrigatório para a consulta do recibo.");
            $resultado = consultarPixRecebido($ambienteConfig, $sslCertFile, $sslKeyFile, $caInfoFile, $token, $e2eid);
            echo json_encode(['success' => true, 'data' => $resultado]);
            break;

        case 'consultar_lista_pix':
            $inicio = $_GET['inicio'] ?? null;
            $fim = $_GET['fim'] ?? null;
            if (!$inicio || !$fim) throw new Exception("Data de início e fim são obrigatórias.");
            
            $resultado = consultarListaPixRecebidos($ambienteConfig, $sslCertFile, $sslKeyFile, $caInfoFile, $token, $inicio, $fim);
            echo json_encode(['success' => true, 'data' => $resultado]);
            break;

        default:
            throw new Exception("Ação inválida ou não especificada.");
    }

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
