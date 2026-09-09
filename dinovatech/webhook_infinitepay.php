<?php
// Endpoint Webhook para Notificações da InfinitePay

header('Content-Type: application/json; charset=utf-8');

$inputRaw = file_get_contents('php://input');
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

// Extrai id_fatura de order_nsu (ex: "fatura#12" ou "12")
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

$link = DBConnect();
if (!$link) {
    http_response_code(500);
    echo json_encode(['error' => 'Falha de banco de dados']);
    exit();
}

$idSafe = mysqli_real_escape_string($link, $idFatura);
$qFatura = "SELECT * FROM Faturas WHERE id_fatura = '$idSafe' LIMIT 1";
$rFatura = DBExecute($link, $qFatura);

if (!$rFatura || mysqli_num_rows($rFatura) === 0) {
    DBClose($link);
    http_response_code(400);
    echo json_encode(['error' => 'Fatura não encontrada no sistema']);
    exit();
}

$fatura = mysqli_fetch_assoc($rFatura);
$valorPagoDecimal = $paidAmountCents > 0 ? ($paidAmountCents / 100.0) : (float)($fatura['valor_total'] ?? 0);

$txidSafe = mysqli_real_escape_string($link, $transactionNsu ?? '');
$formaPagamentoLabel = ($captureMethod === 'pix') ? 'PIX (InfinitePay)' : 'Cartão de Crédito (InfinitePay)';
$formaSafe = mysqli_real_escape_string($link, $formaPagamentoLabel);
$obs = "Pagamento via InfinitePay. Receipt: " . $receiptUrl;
$obsSafe = mysqli_real_escape_string($link, $obs);
$dataHoje = date('Y-m-d H:i:s');

// Verifica se a transação já foi registrada
$qCheck = "SELECT id_pagamento FROM Pagamentos WHERE id_fatura = '$idSafe' AND txid = '$txidSafe' AND status_pagamento = 'Confirmado' LIMIT 1";
$rCheck = DBExecute($link, $qCheck);

if ($rCheck && mysqli_num_rows($rCheck) > 0) {
    DBClose($link);
    http_response_code(200);
    echo json_encode(['success' => true, 'message' => 'Pagamento já processado anteriormente.']);
    exit();
}

// Insere registro de pagamento confirmado
$qIns = "INSERT INTO Pagamentos (id_fatura, data_pagamento, valor_pago, forma_pagamento, status_pagamento, txid, observacao) 
         VALUES ('$idSafe', '$dataHoje', '$valorPagoDecimal', '$formaSafe', 'Confirmado', '$txidSafe', '$obsSafe')";
DBExecute($link, $qIns);

// Atualiza o status da fatura para 'Pago' se atingir o total líquido
require_once __DIR__ . '/helpers/AppHelper.php';
$calcTotals = AppHelper::calculateFaturaTotals($link, $idFatura);
$valorLiquido = (float)($calcTotals['valor_liquido'] ?? 0);

$rSum = DBExecute($link, "SELECT SUM(valor_pago) AS total_pago FROM Pagamentos WHERE id_fatura = '$idSafe' AND status_pagamento = 'Confirmado'");
$totalPago = 0;
if ($rSum && $rowSum = mysqli_fetch_assoc($rSum)) {
    $totalPago = (float)($rowSum['total_pago'] ?? 0);
}

if ($totalPago >= $valorLiquido) {
    DBExecute($link, "UPDATE Faturas SET status = 'Pago' WHERE id_fatura = '$idSafe'");
}

DBClose($link);

http_response_code(200);
echo json_encode(['success' => true, 'message' => 'Notificação de pagamento processada com sucesso']);
exit();
