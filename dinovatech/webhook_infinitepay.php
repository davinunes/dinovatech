<?php
// Endpoint Webhook para Notificações da InfinitePay

header('Content-Type: application/json; charset=utf-8');

$inputRaw = file_get_contents('php://input');

// 1. Log do Webhook para depuração e auditoria
$logFile = __DIR__ . '/webhook_infinitepay.log';
$logDate = date('Y-m-d H:i:s');
$clientIp = $_SERVER['REMOTE_ADDR'] ?? 'desconhecido';
file_put_contents($logFile, "[{$logDate}] [IP: {$clientIp}] Webhook recebido:\n" . $inputRaw . "\n\n", FILE_APPEND);

if (empty($inputRaw)) {
    http_response_code(400);
    echo json_encode(['error' => 'Payload ausente']);
    exit();
}

$data = json_decode($inputRaw, true);
if (!is_array($data)) {
    http_response_code(400);
    echo json_encode(['error' => 'JSON inválido']);
    exit();
}

$orderNsu = $data['order_nsu'] ?? '';
$transactionNsu = $data['transaction_nsu'] ?? ($data['invoice_slug'] ?? null);
$paidAmountCents = (int)($data['paid_amount'] ?? $data['amount'] ?? 0);
$captureMethod = strtolower((string)($data['capture_method'] ?? 'infinitepay'));
$receiptUrl = $data['receipt_url'] ?? '';

if (empty($orderNsu)) {
    http_response_code(400);
    echo json_encode(['error' => 'order_nsu é obrigatório']);
    exit();
}

// Extrai id_fatura de order_nsu (ex: "fatura#84" ou "84")
$idFatura = null;
if (preg_match('/fatura#(\d+)/i', $orderNsu, $matches)) {
    $idFatura = (int)$matches[1];
} elseif (is_numeric($orderNsu)) {
    $idFatura = (int)$orderNsu;
}

if (!$idFatura) {
    http_response_code(400);
    echo json_encode(['error' => 'Fatura não identificada']);
    exit();
}

// Conecta ao banco de dados
$dbPath = __DIR__ . '/../database.php';
if (!file_exists($dbPath)) {
    $dbPath = __DIR__ . '/database.php';
}
require_once $dbPath;
require_once __DIR__ . '/helpers/InfinitePayHelper.php';

$link = DBConnect();
if (!$link) {
    http_response_code(500);
    echo json_encode(['error' => 'Falha de banco de dados']);
    exit();
}

$result = InfinitePayHelper::processarPagamentoConfirmado(
    $link,
    $idFatura,
    $paidAmountCents,
    $captureMethod,
    $transactionNsu,
    $receiptUrl,
    'Webhook Postback'
);

DBClose($link);

if ($result['success']) {
    http_response_code(200);
    echo json_encode([
        'success' => true,
        'message' => $result['message'],
        'already_processed' => $result['already_processed'] ?? false
    ]);
} else {
    http_response_code(400);
    echo json_encode([
        'error' => $result['message']
    ]);
}
exit();
