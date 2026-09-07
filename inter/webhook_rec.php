<?php
// inter/webhook_rec.php
// Listener para notificações de status de recorrência do Banco Inter (Pix Automático)

header('Content-Type: application/json');

require_once __DIR__ . '/api.php';
require_once __DIR__ . '/PixAutomaticoService.php';
require_once __DIR__ . '/../database.php';

$rawBody = file_get_contents('php://input');
$link = DBConnect();

if (!$link) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Falha na conexão com o banco de dados.']);
    exit;
}

try {
    if (empty($rawBody)) {
        // Inter pode fazer probe GET/HEAD ou enviar payload vazio
        http_response_code(200);
        echo json_encode(['success' => true, 'message' => 'Webhook ativo e aguardando notificações.']);
        exit;
    }

    $payload = json_decode($rawBody, true);
    if (!$payload) {
        throw new Exception("Payload JSON inválido.");
    }

    // Processa o lote de atualizações de recorrência
    $resultado = PixAutomaticoService::tratarWebhookRecorrencia($payload, $link);

    http_response_code(200);
    echo json_encode($resultado);

} catch (Exception $e) {
    // Loga erro mas responde 200/400 de acordo com a falha
    error_log("[Webhook Inter PixRec] Erro: " . $e->getMessage() . " | Payload: " . $rawBody);
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
} finally {
    if ($link) {
        DBClose($link);
    }
}
