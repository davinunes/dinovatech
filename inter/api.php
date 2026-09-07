<?php

// Iniciar a sessão (sempre no início do script que usa sessões)
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Inclui o arquivo de configuração, como você sugeriu.
require_once 'config.php';

/**
 * Obtém o token de acesso. Requer certificados.
 * 
 * @param array $config Configurações do ambiente
 * @param string $sslCert Caminho do certificado (.crt)
 * @param string $sslKey Caminho da chave privada (.key)
 * @param string $caInfo Caminho da cadeia CA (.crt)
 * @param bool $forceRenew Se true, limpa cache da sessão e força nova requisição ao Inter
 * @return string Bearer Access Token
 */
function getInterAccessToken($config, $sslCert, $sslKey, $caInfo, $forceRenew = false)
{
    $urlToken = $config['url_token'];
    $scope = $config['scope'];
    $clientId = $config['client_id'];
    $clientSecret = $config['client_secret'];
    $tokenValidity = $config['token_validity_seconds'];

    $sessionScopeKey = 'inter_api_scope_' . md5($urlToken);
    $sessionTokenKey = 'inter_api_token_' . md5($urlToken);
    $sessionExpiryKey = 'inter_api_token_expiry_' . md5($urlToken);

    if ($forceRenew || ($_SESSION[$sessionScopeKey] ?? '') !== $scope) {
        unset($_SESSION[$sessionTokenKey]);
        unset($_SESSION[$sessionExpiryKey]);
        unset($_SESSION[$sessionScopeKey]);
    }

    $currentToken = $_SESSION[$sessionTokenKey] ?? null;
    $expiresAt = $_SESSION[$sessionExpiryKey] ?? 0;

    if (!$forceRenew && $currentToken && $expiresAt > (time() + 60)) {
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
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($error) {
        throw new Exception("cURL Error on token fetch: " . $error);
    }
    
    $obj = json_decode($response);
    if ($httpCode >= 400 || !$obj || !isset($obj->access_token)) {
        $msg = $obj->error_description ?? ($obj->error ?? $response);
        throw new Exception("Falha ao obter Token OAuth2 no Banco Inter (HTTP {$httpCode}): " . $msg);
    }

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

/**
 * ============================================================================
 * PIX AUTOMÁTICO (RECORRÊNCIA) - BANCO INTER (JORNADA 4 & CICLO DE VIDA)
 * ============================================================================
 */

/**
 * Passo 1 (Jornada 4): Cria uma location de recorrência (/locrec).
 * 
 * @param array $config Configurações do ambiente
 * @param string $sslCert Caminho do certificado (.crt)
 * @param string $sslKey Caminho da chave privada (.key)
 * @param string $caInfo Caminho da cadeia CA (.crt)
 * @param string $bearerToken Token OAuth2
 * @return object Resposta decodificada contendo o id da location
 */
function criarLocationRecorrencia($config, $sslCert, $sslKey, $caInfo, $bearerToken)
{
    $url = $config['url_pix_base'] . '/locrec';
    $headers = [
        'Authorization: Bearer ' . $bearerToken,
        'Content-Type: application/json'
    ];

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_SSLCERT, $sslCert);
    curl_setopt($ch, CURLOPT_SSLKEY, $sslKey);
    curl_setopt($ch, CURLOPT_CAINFO, $caInfo);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode(new stdClass()));
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

    $response = curl_exec($ch);
    $error = curl_error($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($error) {
        throw new Exception("cURL Error on /locrec: " . $error . " | HTTP Code: " . $httpCode);
    }
    if ($httpCode >= 400) {
        throw new Exception("API Error on /locrec: " . $response . " | HTTP Code: " . $httpCode);
    }

    $decoded = json_decode($response);
    if (!$decoded || empty($decoded->id)) {
        throw new Exception("Resposta inválida do Inter em /locrec: " . $response);
    }

    return $decoded;
}

/**
 * Passo 2 (Jornada 4): Cria uma Cobrança com Vencimento (CobV) vinculada à fatura atual.
 * 
 * @param array $config Configurações do ambiente
 * @param string $sslCert Caminho do certificado (.crt)
 * @param string $sslKey Caminho da chave privada (.key)
 * @param string $caInfo Caminho da cadeia CA (.crt)
 * @param string $bearerToken Token OAuth2
 * @param string $txid Identificador único da cobrança
 * @param array $data Dados da cobrança com vencimento
 * @return object Resposta da CobV
 */
function criarCobvComVencimento($config, $sslCert, $sslKey, $caInfo, $bearerToken, $txid, $data)
{
    $url = $config['url_pix_base'] . '/cobv/' . $txid;
    $headers = [
        'Authorization: Bearer ' . $bearerToken,
        'Content-Type: application/json'
    ];

    $jsonData = json_encode($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PUT');
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

    if ($error) {
        throw new Exception("cURL Error on PUT /cobv/{$txid}: " . $error . " | HTTP Code: " . $httpCode);
    }
    if ($httpCode >= 400) {
        throw new Exception("API Error on PUT /cobv/{$txid}: " . $response . " | HTTP Code: " . $httpCode);
    }

    return json_decode($response);
}

/**
 * Passo 3 (Jornada 4): Cria a Proposta de Recorrência (Contrato Base).
 * 
 * @param array $config Configurações do ambiente
 * @param string $sslCert Caminho do certificado (.crt)
 * @param string $sslKey Caminho da chave privada (.key)
 * @param string $caInfo Caminho da cadeia CA (.crt)
 * @param string $bearerToken Token OAuth2
 * @param array $data Dados da proposta de recorrência (vinculo, calendario, valor, loc, etc.)
 * @return object Resposta decodificada contendo idRec
 */
function criarRecorrenciaContrato($config, $sslCert, $sslKey, $caInfo, $bearerToken, $data)
{
    $url = $config['url_pix_base'] . '/rec';
    $contaCorrenteLimpa = preg_replace('/[^0-9]/', '', $config['conta_corrente'] ?? '');

    $headers = [
        'Authorization: Bearer ' . $bearerToken,
        'Content-Type: application/json'
    ];

    if (!empty($contaCorrenteLimpa)) {
        $headers[] = 'x-conta-corrente: ' . $contaCorrenteLimpa;
    }

    $jsonData = json_encode($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
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

    if ($error) {
        throw new Exception("cURL Error on POST /rec: " . $error . " | HTTP Code: " . $httpCode);
    }
    if ($httpCode >= 400) {
        throw new Exception("API Error on POST /rec: " . $response . " | HTTP Code: " . $httpCode);
    }

    $decoded = json_decode($response);
    if (!$decoded || empty($decoded->idRec)) {
        throw new Exception("Resposta inválida do Inter em POST /rec: " . $response);
    }

    return $decoded;
}

/**
 * Passo 4 (Jornada 4): Gera e consulta o QR Code Combinado (Fatura Atual + Recorrência).
 * 
 * @param array $config Configurações do ambiente
 * @param string $sslCert Caminho do certificado (.crt)
 * @param string $sslKey Caminho da chave privada (.key)
 * @param string $caInfo Caminho da cadeia CA (.crt)
 * @param string $bearerToken Token OAuth2
 * @param string $idRec Identificador da recorrência (RN...)
 * @param string $txid TXID da CobV da fatura atual
 * @return object Resposta com os dados do QR Code Jornada 4 (pixCopiaECola / emv)
 */
function consultarRecorrenciaJornada4($config, $sslCert, $sslKey, $caInfo, $bearerToken, $idRec, $txid)
{
    $queryParams = http_build_query([
        'idRec' => $idRec,
        'txid' => $txid
    ]);
    $url = $config['url_pix_base'] . '/rec?' . $queryParams;

    $headers = [
        'Authorization: Bearer ' . $bearerToken,
        'Content-Type: application/json'
    ];

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
        throw new Exception("cURL Error on GET /rec?idRec&txid: " . $error . " | HTTP Code: " . $httpCode);
    }
    if ($httpCode >= 400) {
        throw new Exception("API Error on GET /rec?idRec&txid: " . $response . " | HTTP Code: " . $httpCode);
    }

    return json_decode($response);
}

/**
 * Consulta ativa (Fallback) do status de uma recorrência por idRec.
 * 
 * @param array $config Configurações do ambiente
 * @param string $sslCert Caminho do certificado (.crt)
 * @param string $sslKey Caminho da chave privada (.key)
 * @param string $caInfo Caminho da cadeia CA (.crt)
 * @param string $bearerToken Token OAuth2
 * @param string $idRec Identificador da recorrência (RN...)
 * @return object Resposta com o status da recorrência
 */
function consultarStatusRecorrencia($config, $sslCert, $sslKey, $caInfo, $bearerToken, $idRec)
{
    $url = $config['url_pix_base'] . '/rec/' . rawurlencode($idRec);

    $headers = [
        'Authorization: Bearer ' . $bearerToken,
        'Content-Type: application/json'
    ];

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
        throw new Exception("cURL Error on GET /rec/{$idRec}: " . $error . " | HTTP Code: " . $httpCode);
    }
    if ($httpCode >= 400) {
        throw new Exception("API Error on GET /rec/{$idRec}: " . $response . " | HTTP Code: " . $httpCode);
    }

    return json_decode($response);
}

/**
 * Cria Cobrança Recorrente Subsequente (POST /cobr) - Débito Automático Mensal.
 * 
 * @param array $config Configurações do ambiente
 * @param string $sslCert Caminho do certificado (.crt)
 * @param string $sslKey Caminho da chave privada (.key)
 * @param string $caInfo Caminho da cadeia CA (.crt)
 * @param string $bearerToken Token OAuth2
 * @param array $data Dados da cobrança recorrente
 * @return object Resposta com txid da instrução de débito
 */
function criarCobrancaRecorrenteSubsequente($config, $sslCert, $sslKey, $caInfo, $bearerToken, $data)
{
    $url = $config['url_pix_base'] . '/cobr';
    $contaCorrenteLimpa = preg_replace('/[^0-9]/', '', $config['conta_corrente'] ?? '');

    $headers = [
        'Authorization: Bearer ' . $bearerToken,
        'Content-Type: application/json'
    ];

    if (!empty($contaCorrenteLimpa)) {
        $headers[] = 'x-conta-corrente: ' . $contaCorrenteLimpa;
    }

    $jsonData = json_encode($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
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

    if ($error) {
        throw new Exception("cURL Error on POST /cobr: " . $error . " | HTTP Code: " . $httpCode);
    }
    if ($httpCode >= 400) {
        throw new Exception("API Error on POST /cobr: " . $response . " | HTTP Code: " . $httpCode);
    }

    return json_decode($response);
}

/**
 * Cancela uma recorrência ativa (Contrato de Pix Automático) no Banco Inter.
 * 
 * @param array $config Configurações do ambiente
 * @param string $sslCert Caminho do certificado (.crt)
 * @param string $sslKey Caminho da chave privada (.key)
 * @param string $caInfo Caminho da cadeia CA (.crt)
 * @param string $bearerToken Token OAuth2
 * @param string $idRec Identificador da recorrência (RN...)
 * @return object Resposta com a recorrência cancelada
 */
function cancelarRecorrenciaContrato($config, $sslCert, $sslKey, $caInfo, $bearerToken, $idRec)
{
    $url = $config['url_pix_base'] . '/rec/' . rawurlencode($idRec);
    $headers = [
        'Authorization: Bearer ' . $bearerToken,
        'Content-Type: application/json'
    ];

    $payload = ['status' => 'CANCELADA'];
    $jsonData = json_encode($payload);

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PATCH');
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

    if ($error) {
        throw new Exception("cURL Error on PATCH /rec/{$idRec}: " . $error . " | HTTP Code: " . $httpCode);
    }
    if ($httpCode >= 400) {
        throw new Exception("API Error on PATCH /rec/{$idRec}: " . $response . " | HTTP Code: " . $httpCode);
    }

    return json_decode($response);
}

/**
 * Cancela uma cobrança recorrente individual de uma fatura específica (PATCH /cobr/{txid}).
 * Respeita a regra Bacen de cancelamento até as 22h00 do dia anterior à liquidação.
 * 
 * @param array $config Configurações do ambiente
 * @param string $sslCert Caminho do certificado (.crt)
 * @param string $sslKey Caminho da chave privada (.key)
 * @param string $caInfo Caminho da cadeia CA (.crt)
 * @param string $bearerToken Token OAuth2
 * @param string $txid Identificador da cobrança
 * @return object Resposta da cobrança cancelada
 */
function cancelarCobrancaIndividual($config, $sslCert, $sslKey, $caInfo, $bearerToken, $txid)
{
    $url = $config['url_pix_base'] . '/cobr/' . rawurlencode($txid);
    $headers = [
        'Authorization: Bearer ' . $bearerToken,
        'Content-Type: application/json'
    ];

    $payload = ['status' => 'CANCELADA'];
    $jsonData = json_encode($payload);

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PATCH');
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

    if ($error) {
        throw new Exception("cURL Error on PATCH /cobr/{$txid}: " . $error . " | HTTP Code: " . $httpCode);
    }
    if ($httpCode >= 400) {
        throw new Exception("API Error on PATCH /cobr/{$txid}: " . $response . " | HTTP Code: " . $httpCode);
    }

    return json_decode($response);
}

/**
 * Configura / Atualiza a URL do Webhook de Recorrência no Banco Inter (PUT /webhookrec).
 * 
 * @param array $config Configurações do ambiente
 * @param string $sslCert Caminho do certificado (.crt)
 * @param string $sslKey Caminho da chave privada (.key)
 * @param string $caInfo Caminho da cadeia CA (.crt)
 * @param string $bearerToken Token OAuth2
 * @param string $webhookUrl URL de callback pública do webhook
 * @return mixed Resposta do Inter
 */
function configurarWebhookRecorrencia($config, $sslCert, $sslKey, $caInfo, $bearerToken, $webhookUrl)
{
    $url = $config['url_pix_base'] . '/webhookrec';
    $headers = [
        'Authorization: Bearer ' . $bearerToken,
        'Content-Type: application/json'
    ];

    $payload = ['webhookUrl' => $webhookUrl];
    $jsonData = json_encode($payload, JSON_UNESCAPED_SLASHES);

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PUT');
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

    if ($error) {
        throw new Exception("cURL Error on PUT /webhookrec: " . $error . " | HTTP Code: " . $httpCode);
    }
    if ($httpCode >= 400) {
        throw new Exception("API Error on PUT /webhookrec: " . $response . " | HTTP Code: " . $httpCode);
    }

    return json_decode($response) ?: true;
}

/**
 * Consulta a URL do Webhook de Recorrência cadastrada no Banco Inter (GET /webhookrec).
 * 
 * @param array $config Configurações do ambiente
 * @param string $sslCert Caminho do certificado (.crt)
 * @param string $sslKey Caminho da chave privada (.key)
 * @param string $caInfo Caminho da cadeia CA (.crt)
 * @param string $bearerToken Token OAuth2
 * @return object Resposta contendo webhookUrl
 */
function consultarWebhookRecorrencia($config, $sslCert, $sslKey, $caInfo, $bearerToken)
{
    $url = $config['url_pix_base'] . '/webhookrec';
    $headers = [
        'Authorization: Bearer ' . $bearerToken,
        'Content-Type: application/json'
    ];

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
        throw new Exception("cURL Error on GET /webhookrec: " . $error . " | HTTP Code: " . $httpCode);
    }
    if ($httpCode >= 400) {
        throw new Exception("API Error on GET /webhookrec: " . $response . " | HTTP Code: " . $httpCode);
    }

    return json_decode($response);
}
