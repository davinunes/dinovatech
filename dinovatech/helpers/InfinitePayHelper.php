<?php

require_once __DIR__ . '/AppHelper.php';

class InfinitePayHelper
{
    /**
     * Sanitiza o Handle (InfiniteTag), removendo $ inicial e espaços.
     */
    public static function cleanHandle(?string $handle): string
    {
        if (empty($handle)) {
            return '';
        }
        $handle = trim($handle);
        return ltrim($handle, '$');
    }

    /**
     * Formata um número de telefone para o padrão E.164 (+55...).
     */
    public static function formatPhoneE164(?string $phone): ?string
    {
        if (empty($phone)) {
            return null;
        }
        $digits = preg_replace('/[^0-9]/', '', $phone);
        if (empty($digits)) {
            return null;
        }

        if (strlen($digits) === 10 || strlen($digits) === 11) {
            return '+55' . $digits;
        } elseif (strlen($digits) === 12 || strlen($digits) === 13) {
            return '+' . $digits;
        }

        return '+' . $digits;
    }

    /**
     * Retorna a URL raiz do site (sem subpastas /dinovatech ou /cliente).
     */
    public static function getSiteRootUrl(): string
    {
        $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || ($_SERVER['SERVER_PORT'] ?? 80) == 443 ? 'https' : 'http';
        $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
        
        $scriptPath = $_SERVER['SCRIPT_NAME'] ?? '/';
        $dir = dirname($scriptPath);
        $dir = str_replace('\\', '/', $dir);
        
        // Remove sufixos /dinovatech ou /cliente para obter a raiz do site
        $dir = preg_replace('~/(dinovatech|cliente)$~i', '', $dir);
        if ($dir === '/' || $dir === '.') {
            $dir = '';
        }

        return rtrim("{$protocol}://{$host}{$dir}", '/');
    }

    /**
     * Garante a existência de colunas no banco de dados para salvar dados do checkout.
     */
    public static function ensureFaturasColumns($link): void
    {
        if (!$link) return;
        $res = DBExecute($link, "SHOW COLUMNS FROM Faturas LIKE 'infinitepay_checkout_url'");
        if (!$res || mysqli_num_rows($res) === 0) {
            DBExecute($link, "ALTER TABLE Faturas ADD COLUMN infinitepay_checkout_url VARCHAR(500) NULL");
            DBExecute($link, "ALTER TABLE Faturas ADD COLUMN infinitepay_slug VARCHAR(255) NULL");
            DBExecute($link, "ALTER TABLE Faturas ADD COLUMN infinitepay_nsu VARCHAR(255) NULL");
        }
    }

    /**
     * Gera o link de checkout via API InfinitePay para uma fatura.
     */
    public static function gerarLinkCheckout($link, $id_fatura): array
    {
        if (!$link || empty($id_fatura)) {
            return ['success' => false, 'message' => 'Parâmetros inválidos para gerar checkout.'];
        }

        // 1. Carrega configurações do emissor
        $resConfig = DBExecute($link, "SELECT * FROM ConfiguracoesEmissor LIMIT 1");
        if (!$resConfig || mysqli_num_rows($resConfig) === 0) {
            return ['success' => false, 'message' => 'Configurações do emissor não encontradas.'];
        }
        $config = mysqli_fetch_assoc($resConfig);

        $ativo = (int)($config['infinitepay_ativo'] ?? 0);
        $rawHandle = $config['infinitepay_handle'] ?? '';
        $handle = self::cleanHandle($rawHandle);

        if ($ativo !== 1 || empty($handle)) {
            return ['success' => false, 'message' => 'Integração com InfinitePay inativa ou Handle não configurado.'];
        }

        // 2. Carrega dados da fatura e cliente
        $id_safe = mysqli_real_escape_string($link, $id_fatura);
        $queryFatura = "SELECT F.*, C.nome AS nome_cliente, C.cpf_cnpj, C.email AS email_cliente, C.telefone AS telefone_cliente,
                               C.endereco, C.numero, C.complemento, C.bairro, C.cep, C.uf
                        FROM Faturas F 
                        JOIN Clientes C ON F.id_cliente = C.id_cliente 
                        WHERE F.id_fatura = '$id_safe' LIMIT 1";
        $resFatura = DBExecute($link, $queryFatura);
        if (!$resFatura || mysqli_num_rows($resFatura) === 0) {
            return ['success' => false, 'message' => 'Fatura não encontrada.'];
        }
        $fatura = mysqli_fetch_assoc($resFatura);

        // 3. Calcula saldo devedor
        $calcTotals = AppHelper::calculateFaturaTotals($link, $id_fatura);
        $valorLiquido = (float)($calcTotals['valor_liquido'] ?? 0);

        // Total pago confirmado
        $resPag = DBExecute($link, "SELECT SUM(valor_pago) AS total_pago FROM Pagamentos WHERE id_fatura = '$id_safe' AND status_pagamento = 'Confirmado'");
        $totalPago = 0;
        if ($resPag && $rowPag = mysqli_fetch_assoc($resPag)) {
            $totalPago = (float)($rowPag['total_pago'] ?? 0);
        }

        $saldoDevedor = $valorLiquido - $totalPago;
        if ($saldoDevedor <= 0) {
            return ['success' => false, 'message' => 'Esta fatura já se encontra totalmente paga.'];
        }

        $totalCents = (int)round($saldoDevedor * 100);

        // 4. Opções configuradas
        $usarWebhook = (int)($config['infinitepay_usar_webhook'] ?? 1) === 1;
        $usarRedirect = (int)($config['infinitepay_usar_redirect'] ?? 1) === 1;
        $detalharItens = (int)($config['infinitepay_detalhar_itens'] ?? 1) === 1;
        $enviarCliente = (int)($config['infinitepay_enviar_cliente'] ?? 1) === 1;
        $enviarEndereco = (int)($config['infinitepay_enviar_endereco'] ?? 1) === 1;

        // 5. Montagem dos Itens
        $itemsPayload = [];
        if ($detalharItens) {
            $resItems = DBExecute($link, "SELECT I.*, S.nome_servico FROM ItensFatura I JOIN Servicos S ON I.id_servico = S.id_servico WHERE I.id_fatura = '$id_safe'");
            $sumItemsCents = 0;
            $tempItems = [];

            if ($resItems && mysqli_num_rows($resItems) > 0) {
                while ($it = mysqli_fetch_assoc($resItems)) {
                    $qty = max(1, (int)($it['quantidade'] ?? 1));
                    $priceCents = (int)round((float)($it['valor_unitario'] ?? 0) * 100);
                    $sumItemsCents += ($qty * $priceCents);
                    $tempItems[] = [
                        'quantity' => $qty,
                        'price' => $priceCents,
                        'description' => mb_substr((string)($it['nome_servico'] ?? 'Item de Serviço'), 0, 250)
                    ];
                }
            }

            if ($sumItemsCents === $totalCents && !empty($tempItems)) {
                $itemsPayload = $tempItems;
            }
        }

        if (empty($itemsPayload)) {
            $itemsPayload = [
                [
                    'quantity' => 1,
                    'price' => $totalCents,
                    'description' => "Pagamento da Fatura #{$id_fatura}"
                ]
            ];
        }

        // 6. Montagem da Payload completa
        $siteRoot = self::getSiteRootUrl();
        $orderNsu = "fatura-{$id_fatura}";
        $payload = [
            'handle' => $handle,
            'order_nsu' => $orderNsu,
            'items' => $itemsPayload
        ];

        if ($usarRedirect) {
            $tokenParam = !empty($fatura['token_acesso']) ? "&token=" . urlencode($fatura['token_acesso']) : "";
            $payload['redirect_url'] = "{$siteRoot}/cliente/fatura.php?id={$id_fatura}{$tokenParam}";
        }

        if ($usarWebhook) {
            $payload['webhook_url'] = "{$siteRoot}/dinovatech/webhook_infinitepay.php";
        }

        if ($enviarCliente && !empty($fatura['nome_cliente'])) {
            $customerData = [
                'name' => mb_substr($fatura['nome_cliente'], 0, 150)
            ];
            if (!empty($fatura['email_cliente']) && filter_var($fatura['email_cliente'], FILTER_VALIDATE_EMAIL)) {
                $customerData['email'] = $fatura['email_cliente'];
            }
            $phoneFormatted = self::formatPhoneE164($fatura['telefone_cliente'] ?? '');
            if ($phoneFormatted) {
                $customerData['phone_number'] = $phoneFormatted;
            }
            $payload['customer'] = $customerData;
        }

        if ($enviarEndereco && !empty($fatura['endereco'])) {
            $cepClean = preg_replace('/[^0-9]/', '', $fatura['cep'] ?? '');
            if (strlen($cepClean) === 8) {
                $payload['address'] = [
                    'cep' => $cepClean,
                    'street' => mb_substr($fatura['endereco'], 0, 150),
                    'neighborhood' => mb_substr($fatura['bairro'] ?? 'Centro', 0, 100),
                    'number' => mb_substr($fatura['numero'] ?? 'S/N', 0, 20),
                    'complement' => mb_substr($fatura['complemento'] ?? '', 0, 100)
                ];
            }
        }

        // 7. Requisição POST para API InfinitePay
        $apiUrl = 'https://api.checkout.infinitepay.io/links';
        $jsonPayload = json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        $ch = curl_init($apiUrl);
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $jsonPayload,
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'Accept: application/json'
            ],
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 15,
            CURLOPT_SSL_VERIFYPEER => true
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlErr = curl_error($ch);
        curl_close($ch);

        if ($curlErr) {
            return ['success' => false, 'message' => "Erro de conexão cURL: {$curlErr}"];
        }

        $resData = json_decode($response, true);
        if ($httpCode >= 200 && $httpCode < 300 && is_array($resData)) {
            $checkoutUrl = $resData['url'] ?? $resData['checkout_url'] ?? $resData['link'] ?? null;
            $slug = $resData['invoice_slug'] ?? $resData['slug'] ?? null;

            if ($checkoutUrl) {
                // Guarda os identificadores na tabela Faturas
                self::ensureFaturasColumns($link);
                $urlSafe = mysqli_real_escape_string($link, $checkoutUrl);
                $slugSafe = mysqli_real_escape_string($link, (string)$slug);
                $nsuSafe = mysqli_real_escape_string($link, $orderNsu);

                DBExecute($link, "UPDATE Faturas SET infinitepay_checkout_url = '$urlSafe', infinitepay_slug = '$slugSafe', infinitepay_nsu = '$nsuSafe' WHERE id_fatura = '$id_safe'");

                return [
                    'success' => true,
                    'checkout_url' => $checkoutUrl,
                    'order_nsu' => $orderNsu,
                    'slug' => $slug,
                    'payload' => $payload,
                    'raw_response' => $resData
                ];
            }
        }

        $errMsg = $resData['message'] ?? $resData['error'] ?? "Erro HTTP {$httpCode} ao comunicar com a InfinitePay.";
        if (is_array($errMsg)) {
            $errMsg = json_encode($errMsg, JSON_UNESCAPED_UNICODE);
        }

        return [
            'success' => false,
            'message' => $errMsg,
            'raw_response' => $response
        ];
    }

    /**
     * Consulta o status do pagamento diretamente na API da InfinitePay (POST /payment_check).
     */
    public static function verificarStatusPagamento($link, $id_fatura): array
    {
        if (!$link || empty($id_fatura)) {
            return ['success' => false, 'message' => 'Parâmetros inválidos para verificação.'];
        }

        $id_safe = mysqli_real_escape_string($link, $id_fatura);
        $qFatura = "SELECT F.*, C.nome AS nome_cliente FROM Faturas F JOIN Clientes C ON F.id_cliente = C.id_cliente WHERE F.id_fatura = '$id_safe' LIMIT 1";
        $resFatura = DBExecute($link, $qFatura);
        if (!$resFatura || mysqli_num_rows($resFatura) === 0) {
            return ['success' => false, 'message' => 'Fatura não encontrada.'];
        }
        $fatura = mysqli_fetch_assoc($resFatura);

        $resConfig = DBExecute($link, "SELECT * FROM ConfiguracoesEmissor LIMIT 1");
        if (!$resConfig || mysqli_num_rows($resConfig) === 0) {
            return ['success' => false, 'message' => 'Configurações do emissor não encontradas.'];
        }
        $config = mysqli_fetch_assoc($resConfig);
        $handle = self::cleanHandle($config['infinitepay_handle'] ?? '');
        if (empty($handle)) {
            return ['success' => false, 'message' => 'Handle do InfinitePay não configurado.'];
        }

        $orderNsu = !empty($fatura['infinitepay_nsu']) ? $fatura['infinitepay_nsu'] : "fatura-{$id_fatura}";
        $slug = $fatura['infinitepay_slug'] ?? '';

        $payloadCheck = [
            'handle' => $handle,
            'order_nsu' => $orderNsu
        ];
        if (!empty($slug)) {
            $payloadCheck['slug'] = $slug;
        }

        $apiUrl = 'https://api.checkout.infinitepay.io/payment_check';
        $jsonPayload = json_encode($payloadCheck);

        $ch = curl_init($apiUrl);
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $jsonPayload,
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'Accept: application/json'
            ],
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 15,
            CURLOPT_SSL_VERIFYPEER => true
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlErr = curl_error($ch);
        curl_close($ch);

        if ($curlErr) {
            return ['success' => false, 'message' => "Erro cURL ao consultar InfinitePay: {$curlErr}"];
        }

        $resData = json_decode($response, true);

        // Fallback: Se não encontrou paid com order_nsu atual, tenta formatos alternativos ("fatura-84", "fatura#84", "84")
        if (is_array($resData) && empty($resData['paid'])) {
            $altNsus = array_unique([
                "fatura-{$id_fatura}",
                "fatura#{$id_fatura}",
                (string)$id_fatura
            ]);
            foreach ($altNsus as $altNsu) {
                if ($altNsu === $orderNsu) continue;
                $payloadCheck2 = ['handle' => $handle, 'order_nsu' => $altNsu];
                $ch2 = curl_init($apiUrl);
                curl_setopt_array($ch2, [
                    CURLOPT_POST => true,
                    CURLOPT_POSTFIELDS => json_encode($payloadCheck2),
                    CURLOPT_HTTPHEADER => ['Content-Type: application/json', 'Accept: application/json'],
                    CURLOPT_RETURNTRANSFER => true,
                    CURLOPT_TIMEOUT => 10,
                    CURLOPT_SSL_VERIFYPEER => true
                ]);
                $response2 = curl_exec($ch2);
                curl_close($ch2);
                $resData2 = json_decode($response2, true);
                if (is_array($resData2) && !empty($resData2['paid'])) {
                    $resData = $resData2;
                    break;
                }
            }
        }

        if (is_array($resData) && !empty($resData['paid'])) {
            $paidAmountCents = (int)($resData['paid_amount'] ?? $resData['amount'] ?? 0);
            $captureMethod = strtolower((string)($resData['capture_method'] ?? 'infinitepay'));
            $transactionNsu = $resData['transaction_nsu'] ?? $resData['slug'] ?? $resData['invoice_slug'] ?? $slug ?? ('infinitepay_' . time());
            $receiptUrl = $resData['receipt_url'] ?? '';

            // Atualiza slug se foi devolvido
            $newSlug = $resData['invoice_slug'] ?? $resData['slug'] ?? $transactionNsu;
            if (!empty($newSlug) && (empty($fatura['infinitepay_slug']) || $fatura['infinitepay_slug'] === '')) {
                $slugEsc = mysqli_real_escape_string($link, $newSlug);
                DBExecute($link, "UPDATE Faturas SET infinitepay_slug = '$slugEsc' WHERE id_fatura = '$id_safe'");
            }

            $processRes = self::processarPagamentoConfirmado(
                $link,
                (int)$id_fatura,
                $paidAmountCents,
                $captureMethod,
                $transactionNsu,
                $receiptUrl,
                'Verificação manual via API InfinitePay'
            );

            return [
                'success' => true,
                'paid' => true,
                'message' => 'Pagamento confirmado e registrado com sucesso!',
                'data' => $resData,
                'process_details' => $processRes
            ];
        }

        return [
            'success' => true,
            'paid' => false,
            'message' => 'Pagamento ainda não foi identificado ou está pendente na InfinitePay.',
            'data' => $resData
        ];
    }

    /**
     * Processa e registra um pagamento confirmado no banco de dados.
     */
    public static function processarPagamentoConfirmado($link, int $idFatura, int $paidAmountCents, string $captureMethod, ?string $txid, ?string $receiptUrl = '', string $origem = 'Webhook'): array
    {
        $idSafe = mysqli_real_escape_string($link, $idFatura);
        $qFatura = "SELECT * FROM Faturas WHERE id_fatura = '$idSafe' LIMIT 1";
        $rFatura = DBExecute($link, $qFatura);
        if (!$rFatura || mysqli_num_rows($rFatura) === 0) {
            return ['success' => false, 'message' => 'Fatura não encontrada.'];
        }
        $fatura = mysqli_fetch_assoc($rFatura);

        // Se slug estiver vazio na fatura e veio no txid/origem, salva
        if (!empty($txid) && empty($fatura['infinitepay_slug'])) {
            $txidEsc = mysqli_real_escape_string($link, $txid);
            DBExecute($link, "UPDATE Faturas SET infinitepay_slug = '$txidEsc' WHERE id_fatura = '$idSafe'");
        }

        $valorPagoDecimal = $paidAmountCents > 0 ? ($paidAmountCents / 100.0) : (float)($fatura['valor_total'] ?? 0);
        $formaPagamentoLabel = (strtolower($captureMethod) === 'pix') ? 'PIX (InfinitePay)' : 'Cartão de Crédito (InfinitePay)';
        $formaSafe = mysqli_real_escape_string($link, $formaPagamentoLabel);
        
        $obs = "Pagamento via InfinitePay ({$origem}).";
        if (!empty($receiptUrl)) {
            $obs .= " Comprovante: " . $receiptUrl;
        }
        $obsSafe = mysqli_real_escape_string($link, $obs);
        $txidSafe = mysqli_real_escape_string($link, $txid ?? ('infinitepay_' . $idFatura));
        $dataHoje = date('Y-m-d H:i:s');

        // Verifica duplicidade pelo txid
        $qCheck = "SELECT id_pagamento FROM Pagamentos WHERE id_fatura = '$idSafe' AND txid = '$txidSafe' AND status_pagamento = 'Confirmado' LIMIT 1";
        $rCheck = DBExecute($link, $qCheck);
        if ($rCheck && mysqli_num_rows($rCheck) > 0) {
            return ['success' => true, 'already_processed' => true, 'message' => 'Pagamento já processado anteriormente.'];
        }

        // Insere registro em Pagamentos
        $qIns = "INSERT INTO Pagamentos (id_fatura, data_pagamento, valor_pago, forma_pagamento, status_pagamento, txid, observacao) 
                 VALUES ('$idSafe', '$dataHoje', '$valorPagoDecimal', '$formaSafe', 'Confirmado', '$txidSafe', '$obsSafe')";
        DBExecute($link, $qIns);

        // Atualiza status da Fatura se valor total atingido
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

        return ['success' => true, 'already_processed' => false, 'message' => 'Pagamento liquidado com sucesso!'];
    }
}
