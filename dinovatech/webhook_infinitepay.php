<?php
// Endpoint Webhook para Notificações da InfinitePay

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/helpers/AppHelper.php';

$inputRaw = file_get_contents('php://input');

// 1. Log do Webhook para depuração e auditoria (Captura IP real via Caddy/Proxy)
$logFile = __DIR__ . '/webhook_infinitepay.log';
$logDate = date('Y-m-d H:i:s');
$clientIp = AppHelper::getClientIP();
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
$amountCents = (int)($data['amount'] ?? 0);
$paidAmountCents = (int)($data['paid_amount'] ?? $data['amount'] ?? 0);
$captureMethod = strtolower((string)($data['capture_method'] ?? 'infinitepay'));
$receiptUrl = $data['receipt_url'] ?? '';

if (empty($orderNsu) && empty($transactionNsu)) {
    http_response_code(400);
    echo json_encode(['error' => 'order_nsu ou transaction_nsu é obrigatório']);
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

// 1º Meio de identificação: Extração por expressão regular em order_nsu (ex: "fatura#84", "FATURA-84", "84")
$idFatura = null;
if (!empty($orderNsu)) {
    if (preg_match('/(?:fatura|fat)[#\-_]?(\d+)/i', $orderNsu, $matches)) {
        $idFatura = (int)$matches[1];
    } elseif (is_numeric($orderNsu)) {
        $idFatura = (int)$orderNsu;
    }
}

// 2º Meio de identificação (Redundância/Dobra): Busca na tabela Faturas por infinitepay_nsu ou infinitepay_slug
if (!$idFatura) {
    $nsuEsc = mysqli_real_escape_string($link, (string)$orderNsu);
    $slugEsc = mysqli_real_escape_string($link, (string)$transactionNsu);
    
    $qFind = "SELECT id_fatura FROM Faturas 
              WHERE (infinitepay_nsu IS NOT NULL AND infinitepay_nsu != '' AND infinitepay_nsu = '$nsuEsc') 
                 OR (infinitepay_slug IS NOT NULL AND infinitepay_slug != '' AND infinitepay_slug = '$slugEsc') 
              LIMIT 1";
    $rFind = DBExecute($link, $qFind);
    if ($rFind && $rowFind = mysqli_fetch_assoc($rFind)) {
        $idFatura = (int)$rowFind['id_fatura'];
    }
}

if (!$idFatura) {
    DBClose($link);
    http_response_code(400);
    echo json_encode(['error' => 'Fatura não identificada nos registros']);
    exit();
}

// Salva o slug e o nsu recebidos no webhook na fatura
InfinitePayHelper::ensureFaturasColumns($link);
$idSafe = mysqli_real_escape_string($link, $idFatura);
$invoiceSlug = $data['invoice_slug'] ?? $data['slug'] ?? '';
if (!empty($invoiceSlug)) {
    $slugEsc = mysqli_real_escape_string($link, $invoiceSlug);
    DBExecute($link, "UPDATE Faturas SET infinitepay_slug = '$slugEsc' WHERE id_fatura = '$idSafe'");
}
if (!empty($orderNsu)) {
    $nsuEsc = mysqli_real_escape_string($link, $orderNsu);
    DBExecute($link, "UPDATE Faturas SET infinitepay_nsu = '$nsuEsc' WHERE id_fatura = '$idSafe'");
}

$result = InfinitePayHelper::processarPagamentoConfirmado(
    $link,
    $idFatura,
    $paidAmountCents,
    $captureMethod,
    $transactionNsu,
    $receiptUrl,
    'Webhook Postback',
    $amountCents
);

DBClose($link);

if ($result['success']) {
    http_response_code(200);
    echo json_encode([
        'success' => true,
        'message' => $result['message'],
        'already_processed' => $result['already_processed'] ?? false,
        'id_fatura' => $idFatura
    ]);
} else {
    http_response_code(400);
    echo json_encode([
        'error' => $result['message']
    ]);
}
exit();
