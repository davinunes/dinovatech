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
     * Retorna a URL base do sistema para Webhook e Redirecionamento.
     */
    public static function getBaseUrl(): string
    {
        $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || ($_SERVER['SERVER_PORT'] ?? 80) == 443 ? 'https' : 'http';
        $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
        
        $scriptPath = $_SERVER['SCRIPT_NAME'] ?? '/';
        $dir = dirname($scriptPath);
        $dir = str_replace('\\', '/', $dir);
        if ($dir === '/' || $dir === '.') {
            $dir = '';
        }

        return rtrim("{$protocol}://{$host}{$dir}", '/');
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

            // Se o somatório dos itens bate com o total líquido (sem retenção/desconto divergente), usa os itens detalhados
            if ($sumItemsCents === $totalCents && !empty($tempItems)) {
                $itemsPayload = $tempItems;
            }
        }

        // Se não foi possível usar itens detalhados ou opção inativa, envia item resumido
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
        $baseUrl = self::getBaseUrl();
        $payload = [
            'handle' => $handle,
            'order_nsu' => "fatura#{$id_fatura}",
            'items' => $itemsPayload
        ];

        if ($usarRedirect) {
            $payload['redirect_url'] = "{$baseUrl}/fatura_view.php?id={$id_fatura}";
        }

        if ($usarWebhook) {
            $payload['webhook_url'] = "{$baseUrl}/webhook_infinitepay.php";
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
            if ($checkoutUrl) {
                return [
                    'success' => true,
                    'checkout_url' => $checkoutUrl,
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
}
