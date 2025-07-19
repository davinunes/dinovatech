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
function getInterAccessToken($config, $sslCert, $sslKey, $caInfo) {
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
        'client_id'     => $clientId,
        'client_secret' => $clientSecret,
        'scope'         => $scope,
        'grant_type'    => 'client_credentials'
    ]));
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/x-www-form-urlencoded']);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

    $response = curl_exec($ch);
    $error = curl_error($ch);
    curl_close($ch);

    if ($error) throw new Exception("cURL Error on token fetch: " . $error);
    $obj = json_decode($response);
    if (!$obj || !isset($obj->access_token)) throw new Exception("Failed to decode token or access_token not found. Response: " . $response);

    $_SESSION[$sessionTokenKey] = $obj->access_token;
    $_SESSION[$sessionExpiryKey] = time() + $tokenValidity;
    $_SESSION[$sessionScopeKey] = $scope;

    return $obj->access_token;
}

/**
 * Cria uma nova cobrança Pix. Requer certificados.
 */
function newInstantPix($config, $sslCert, $sslKey, $caInfo, $bearerToken, $data) {
    $urlPixCob = $config['url_pix_base'] . '/cob';
    
    $payload = [
        "calendario" => ["expiracao" => $data['expiracaoSegundos'] ?? 3600],
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

    if ($error) throw new Exception("cURL Error on new PIX: " . $error . " | HTTP Code: " . $httpCode);
    if ($httpCode >= 400) throw new Exception("API Error on new PIX: " . $response . " | HTTP Code: " . $httpCode);
    
    return json_decode($response);
}

/**
 * Consulta uma cobrança Pix por txid. Requer certificados.
 */
function consultarPix($config, $sslCert, $sslKey, $caInfo, $bearerToken, $txid) {
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

    if ($error) throw new Exception("cURL Error on consult PIX: " . $error . " | HTTP Code: " . $httpCode);
    if ($httpCode >= 400) throw new Exception("API Error on consult PIX: " . $response . " | HTTP Code: " . $httpCode);
    
    return json_decode($response);
}

/**
 * Paga uma cobrança PIX (Sandbox). NÃO requer certificados.
 */
function pagarPix($config, $bearerToken, $txid, $valor) {
    $urlPagamento = $config['url_pix_base'] . '/cob/pagar/' . $txid;
    
    $payload = ['valor' => (float)$valor];
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

    if ($error) throw new Exception("cURL Error on pay PIX: " . $error . " | HTTP Code: " . $httpCode);
    if ($httpCode != 201) throw new Exception("API Error on pay PIX: " . $response . " | HTTP Code: " . $httpCode);
    
    return json_decode($response);
}

/**
 * Consulta um PIX recebido por e2eid. Requer certificados.
 */
function consultarPixRecebido($config, $sslCert, $sslKey, $caInfo, $bearerToken, $e2eid) {
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

    if ($error) throw new Exception("cURL Error on consult received PIX: " . $error . " | HTTP Code: " . $httpCode);
    if ($httpCode >= 400) throw new Exception("API Error on consult received PIX: " . $response . " | HTTP Code: " . $httpCode);
    
    return json_decode($response);
}

/**
 * **NOVA FUNÇÃO**
 * Consulta a lista de PIX recebidos por período. Requer certificados.
 */
function consultarListaPixRecebidos($config, $sslCert, $sslKey, $caInfo, $bearerToken, $inicio, $fim) {
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

    if ($error) throw new Exception("cURL Error on list received PIX: " . $error . " | HTTP Code: " . $httpCode);
    if ($httpCode >= 400) throw new Exception("API Error on list received PIX: " . $response . " | HTTP Code: " . $httpCode);
    
    return json_decode($response);
}
