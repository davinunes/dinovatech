<?php

// Iniciar a sessão (sempre no início do script que usa sessões)
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Inclui o arquivo de configuração, como você sugeriu.
require_once 'config.php';

/**
 * Obtém o token de acesso. Requer certificados.
 */
function getInterAccessToken($config, $sslCert, $sslKey, $caInfo)
{
    $urlToken = $config['url_token'];
    $scope = $config['scope'];
    $clientId = $config['client_id'];
    $clientSecret = $config['client_secret'];
    $tokenValidity = $config['token_validity_seconds'];

    $sessionScopeKey = 'inter_api_scope_' . md5($urlToken);
    if (($_SESSION[$sessionScopeKey] ?? '') !== $scope) {
        unset($_SESSION['inter_api_token_' . md5($urlToken)]);
        unset($_SESSION['inter_api_token_expiry_' . md5($urlToken)]);
    }

    $sessionTokenKey = 'inter_api_token_' . md5($urlToken);
    $sessionExpiryKey = 'inter_api_token_expiry_' . md5($urlToken);
    $currentToken = $_SESSION[$sessionTokenKey] ?? null;
    $expiresAt = $_SESSION[$sessionExpiryKey] ?? 0;

    if ($currentToken && $expiresAt > (time() + 60)) {
        return $currentToken;
    }

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $urlToken);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_SSLCERT, $sslCert);
    curl_setopt($ch, CURLOPT_SSLKEY, $sslKey);
    curl_setopt($ch, CURLOPT_CAINFO, $caInfo);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query([
        'client_id' => $clientId,
        'client_secret' => $clientSecret,
        'scope' => $scope,
        'grant_type' => 'client_credentials'
    ]));
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/x-www-form-urlencoded']);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

    $response = curl_exec($ch);
    $error = curl_error($ch);
    curl_close($ch);

    if ($error)
        throw new Exception("cURL Error on token fetch: " . $error);
    $obj = json_decode($response);
    if (!$obj || !isset($obj->access_token))
        throw new Exception("Failed to decode token or access_token not found. Response: " . $response);

    $_SESSION[$sessionTokenKey] = $obj->access_token;
    $_SESSION[$sessionExpiryKey] = time() + $tokenValidity;
    $_SESSION[$sessionScopeKey] = $scope;

    return $obj->access_token;
}

/**
 * Cria uma nova cobrança Pix. Requer certificados.
 */
function newInstantPix($config, $sslCert, $sslKey, $caInfo, $bearerToken, $data)
{
    $urlPixCob = $config['url_pix_base'] . '/cob';

    $payload = [
        "calendario" => ["expiracao" => $data['expiracaoSegundos'] ?? 172800],
        "devedor" => $data['devedor'],
        "valor" => ["original" => $data['valorOriginal']],
        "chave" => $data['chavePix'],
        "solicitacaoPagador" => $data['solicitacaoPagador']
    ];

    // ** CORREÇÃO: Garante que 'infoAdicionais' seja incluído no payload se existir **
    if (!empty($data['infoAdicionais'])) {
        $payload['infoAdicionais'] = $data['infoAdicionais'];
    }

    $jsonData = json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    $headers = ['Authorization: Bearer ' . $bearerToken, 'Content-Type: application/json'];

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $urlPixCob);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_SSLCERT, $sslCert);
    curl_setopt($ch, CURLOPT_SSLKEY, $sslKey);
    curl_setopt($ch, CURLOPT_CAINFO, $caInfo);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $jsonData);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

    $response = curl_exec($ch);
    $error = curl_error($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($error)
        throw new Exception("cURL Error on new PIX: " . $error . " | HTTP Code: " . $httpCode);
    if ($httpCode >= 400)
        throw new Exception("API Error on new PIX: " . $response . " | HTTP Code: " . $httpCode);

    return json_decode($response);
}

/**
 * Consulta uma cobrança Pix por txid. Requer certificados.
 */
function consultarPix($config, $sslCert, $sslKey, $caInfo, $bearerToken, $txid)
{
    $urlConsulta = $config['url_pix_base'] . '/cob/' . $txid;
    $headers = ['Authorization: Bearer ' . $bearerToken, 'Content-Type: application/json'];

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $urlConsulta);
    curl_setopt($ch, CURLOPT_HTTPGET, true);
    curl_setopt($ch, CURLOPT_SSLCERT, $sslCert);
    curl_setopt($ch, CURLOPT_SSLKEY, $sslKey);
    curl_setopt($ch, CURLOPT_CAINFO, $caInfo);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

    $response = curl_exec($ch);
    $error = curl_error($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($error)
        throw new Exception("cURL Error on consult PIX: " . $error . " | HTTP Code: " . $httpCode);
    if ($httpCode >= 400)
        throw new Exception("API Error on consult PIX: " . $response . " | HTTP Code: " . $httpCode);

    return json_decode($response);
}

/**
 * Paga uma cobrança PIX (Sandbox). NÃO requer certificados.
 */
function pagarPix($config, $bearerToken, $txid, $valor)
{
    $urlPagamento = $config['url_pix_base'] . '/cob/pagar/' . $txid;

    $payload = ['valor' => (float) $valor];
    $jsonData = json_encode($payload);
    $headers = ['Authorization: Bearer ' . $bearerToken, 'Content-Type: application/json'];

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $urlPagamento);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
    curl_setopt($ch, CURLOPT_POSTFIELDS, $jsonData);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

    $response = curl_exec($ch);
    $error = curl_error($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($error)
        throw new Exception("cURL Error on pay PIX: " . $error . " | HTTP Code: " . $httpCode);
    if ($httpCode != 201)
        throw new Exception("API Error on pay PIX: " . $response . " | HTTP Code: " . $httpCode);

    return json_decode($response);
}

/**
 * Consulta um PIX recebido por e2eid. Requer certificados.
 */
function consultarPixRecebido($config, $sslCert, $sslKey, $caInfo, $bearerToken, $e2eid)
{
    $urlConsulta = $config['url_pix_base'] . '/pix/' . $e2eid;
    $headers = ['Authorization: Bearer ' . $bearerToken, 'Content-Type: application/json'];

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $urlConsulta);
    curl_setopt($ch, CURLOPT_HTTPGET, true);
    curl_setopt($ch, CURLOPT_SSLCERT, $sslCert);
    curl_setopt($ch, CURLOPT_SSLKEY, $sslKey);
    curl_setopt($ch, CURLOPT_CAINFO, $caInfo);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

    $response = curl_exec($ch);
    $error = curl_error($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($error)
        throw new Exception("cURL Error on consult received PIX: " . $error . " | HTTP Code: " . $httpCode);
    if ($httpCode >= 400)
        throw new Exception("API Error on consult received PIX: " . $response . " | HTTP Code: " . $httpCode);

    return json_decode($response);
}

/**
 * **NOVA FUNÇÃO**
 * Consulta a lista de PIX recebidos por período. Requer certificados.
 */
function consultarListaPixRecebidos($config, $sslCert, $sslKey, $caInfo, $bearerToken, $inicio, $fim)
{
    // Monta a URL com os parâmetros de data
    $queryParams = http_build_query(['inicio' => $inicio, 'fim' => $fim]);
    $urlConsulta = $config['url_pix_base'] . '/pix?' . $queryParams;

    $headers = ['Authorization: Bearer ' . $bearerToken, 'Content-Type: application/json'];

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $urlConsulta);
    curl_setopt($ch, CURLOPT_HTTPGET, true);
    curl_setopt($ch, CURLOPT_SSLCERT, $sslCert);
    curl_setopt($ch, CURLOPT_SSLKEY, $sslKey);
    curl_setopt($ch, CURLOPT_CAINFO, $caInfo);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

    $response = curl_exec($ch);
    $error = curl_error($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($error)
        throw new Exception("cURL Error on list received PIX: " . $error . " | HTTP Code: " . $httpCode);
    if ($httpCode >= 400)
        throw new Exception("API Error on list received PIX: " . $response . " | HTTP Code: " . $httpCode);

    return json_decode($response);
}

/**
 * Consulta extrato enriquecido/completo com detalhes de transações por período (máx 90 dias).
 * Suporta paginação tradicional e paginação por scroll. Requer certificados e escopo extrato.read.
 * 
 * @param array $config Configurações do ambiente
 * @param string $sslCert Caminho do certificado (.crt)
 * @param string $sslKey Caminho da chave privada (.key)
 * @param string $caInfo Caminho da cadeia CA (.crt)
 * @param string $bearerToken Token OAuth2
 * @param array $params Parâmetros (dataInicio, dataFim, pagina, tamanhoPagina, scrollEnabled, scrollId, tipoOperacao, tipoTransacao)
 * @return object Resposta decodificada em JSON da API do Inter
 */
function consultarExtratoCompleto($config, $sslCert, $sslKey, $caInfo, $bearerToken, $params = [])
{
    $bankingBase = $config['url_banking_base'] ?? str_replace('/pix/v2', '/banking/v2', $config['url_pix_base']);
    $url = $bankingBase . '/extrato/completo';

    if (!empty($params)) {
        $queryParams = [];
        foreach ($params as $key => $val) {
            if ($val !== null && $val !== '') {
                if (is_bool($val)) {
                    $val = $val ? 'true' : 'false';
                }
                $queryParams[$key] = $val;
            }
        }
        if (!empty($queryParams)) {
            $url .= '?' . http_build_query($queryParams);
        }
    }

    $headers = [
        'Authorization: Bearer ' . $bearerToken,
        'Content-Type: application/json'
    ];

    if (!empty($config['conta_corrente'])) {
        $headers[] = 'x-inter-conta-corrente: ' . $config['conta_corrente'];
    }

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_HTTPGET, true);
    curl_setopt($ch, CURLOPT_SSLCERT, $sslCert);
    curl_setopt($ch, CURLOPT_SSLKEY, $sslKey);
    curl_setopt($ch, CURLOPT_CAINFO, $caInfo);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

    $response = curl_exec($ch);
    $error = curl_error($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($error) {
        throw new Exception("cURL Error on consult full statement: " . $error . " | HTTP Code: " . $httpCode);
    }
    if ($httpCode >= 400) {
        throw new Exception("API Error on consult full statement: " . $response . " | HTTP Code: " . $httpCode);
    }

    return json_decode($response);
}

/**
 * Exporta extrato bancário em formato PDF por período. Requer certificados e escopo extrato.read.
 * 
 * @param array $config Configurações do ambiente
 * @param string $sslCert Caminho do certificado (.crt)
 * @param string $sslKey Caminho da chave privada (.key)
 * @param string $caInfo Caminho da cadeia CA (.crt)
 * @param string $bearerToken Token OAuth2
 * @param string $dataInicio Data de início (YYYY-MM-DD)
 * @param string $dataFim Data de fim (YYYY-MM-DD)
 * @return string Conteúdo binário ou resposta do PDF retornado pelo Banco Inter
 */
function exportarExtratoPdf($config, $sslCert, $sslKey, $caInfo, $bearerToken, $dataInicio, $dataFim)
{
    $bankingBase = $config['url_banking_base'] ?? str_replace('/pix/v2', '/banking/v2', $config['url_pix_base']);
    $url = $bankingBase . '/extrato/exportar?' . http_build_query([
        'dataInicio' => $dataInicio,
        'dataFim' => $dataFim
    ]);

    $headers = [
        'Authorization: Bearer ' . $bearerToken,
        'Content-Type: application/json'
    ];

    if (!empty($config['conta_corrente'])) {
        $headers[] = 'x-inter-conta-corrente: ' . $config['conta_corrente'];
    }

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_HTTPGET, true);
    curl_setopt($ch, CURLOPT_SSLCERT, $sslCert);
    curl_setopt($ch, CURLOPT_SSLKEY, $sslKey);
    curl_setopt($ch, CURLOPT_CAINFO, $caInfo);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

    $response = curl_exec($ch);
    $error = curl_error($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($error) {
        throw new Exception("cURL Error on export statement PDF: " . $error . " | HTTP Code: " . $httpCode);
    }
    if ($httpCode >= 400) {
        throw new Exception("API Error on export statement PDF: " . $response . " | HTTP Code: " . $httpCode);
    }

    return $response;
}
