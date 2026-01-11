<?php
header('Content-Type: application/json');

$input = json_decode(file_get_contents('php://input'), true);
if (!$input) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid JSON']);
    exit;
}

$action = $input['action'] ?? 'direct_a1';
$variation = $input['variation'] ?? 'standard'; // standard, uri_empty, no_prefix, no_cdata

// Configuration
$endpoint_type = $input['endpoint'] ?? 'fictitious';
$endpoint_url = ($endpoint_type === 'official')
    ? 'https://www.issnetonline.com.br/apresentacao/df/webservicenfse204/nfse.asmx'
    : 'https://www.issnetonline.com.br/homologaabrasf/webservicenfse204/nfse.asmx';

$certificado_pfx = __DIR__ . '/../certificado/DInovaTech_1001347811.pfx';
$senha_arquivo = __DIR__ . '/../certificado/certificado.php';


// --- ACTION 1: DIRECT A1 SEND ---
if ($action === 'direct_a1') {
    if (file_exists($certificado_pfx)) {
        require $senha_arquivo;
        $pfxContent = file_get_contents($certificado_pfx);
        $certs = [];
        openssl_pkcs12_read($pfxContent, $certs, $senhaCertificado);
    } else {
        echo json_encode(['status' => 'error', 'message' => "Certificate A1 not found"]);
        exit;
    }

    $xmlComponents = buildPedidoXml($input);
    $rootXml = $xmlComponents['root'];
    $rootId = $xmlComponents['id'];

    // Choose URI Reference based on Variation
    $uriRef = "#" . $rootId;
    if ($variation === 'uri_empty') {
        $uriRef = "";
        // If URI is empty, we must NOT have an Id on the root element usually, or it's ignored. 
        // But removing Id might break C14N search if library depends on it. 
        // Let's try to KEEP Id but reference "" (Enveloped).
        // Actually, if URI="", it means "Sign the containing document".
        // Let's remove the ID from the passed XML for this case to be cleaner?
        $rootXml = str_replace(' Id="' . $rootId . '"', '', $rootXml);
    }

    // Sign
    $signedXml = assinarRoot($rootXml, $certs, $uriRef, $variation);

    // Send
    sendSoap($signedXml, $endpoint_url, $certs, $variation);
    exit;
}

// (Action 2 & 3 skipped for brevity as we are focusing on A1 debugging now, but they would need similar updates if A3 was active)

// --- HELPERS ---

function buildPedidoXml($input)
{
    $cnpj = $input['cnpj'] ?? '';
    $cpf = $input['cpf'] ?? '';
    $im = $input['im'] ?? '';
    $numero = $input['numero'] ?? '';
    $dataInicial = $input['dataInicial'] ?? '';
    $dataFinal = $input['dataFinal'] ?? '';

    $pedidoContent = "<Pedido>";
    $pedidoContent .= "<Prestador>";
    $pedidoContent .= "<CpfCnpj>";
    if (!empty($cpf)) {
        $pedidoContent .= "<Cpf>$cpf</Cpf>";
    } else {
        $pedidoContent .= "<Cnpj>$cnpj</Cnpj>";
    }
    $pedidoContent .= "</CpfCnpj>";
    $pedidoContent .= "<InscricaoMunicipal>$im</InscricaoMunicipal>";
    $pedidoContent .= "</Prestador>";

    if (!empty($dataInicial) && !empty($dataFinal)) {
        $pedidoContent .= "<PeriodoCompetencia>";
        $pedidoContent .= "<DataInicial>$dataInicial</DataInicial>";
        $pedidoContent .= "<DataFinal>$dataFinal</DataFinal>";
        $pedidoContent .= "</PeriodoCompetencia>";
    } else {
        $pedidoContent .= "<NumeroNfse>$numero</NumeroNfse>";
    }
    $pedidoContent .= "<Pagina>1</Pagina>";
    $pedidoContent .= "</Pedido>";

    $rootId = "ConsultarNfseServicoPrestadoEnvio";
    $rootXml = '<ConsultarNfseServicoPrestadoEnvio xmlns="http://www.abrasf.org.br/nfse.xsd" Id="' . $rootId . '">' . $pedidoContent . '</ConsultarNfseServicoPrestadoEnvio>';

    return ['root' => $rootXml, 'id' => $rootId];
}

function assinarRoot($xmlString, $certs, $uriRef, $variation)
{
    $dom = new DOMDocument('1.0', 'UTF-8');
    $dom->loadXML($xmlString);

    $canonicalized = $dom->C14N(false, false, null, null);
    $digestValue = base64_encode(sha1($canonicalized, true));

    // Namespace Prefix Logic
    $ns = 'ds'; // Default
    $nsDecl = ' xmlns:ds="http://www.w3.org/2000/09/xmldsig#"';

    if ($variation === 'no_prefix') {
        $ns = '';
        $nsDecl = ' xmlns="http://www.w3.org/2000/09/xmldsig#"';
    }

    $p = $ns ? "$ns:" : ""; // Prefix string

    $signedInfo = "<{$p}SignedInfo{$nsDecl}>" .
        "<{$p}CanonicalizationMethod Algorithm=\"http://www.w3.org/TR/2001/REC-xml-c14n-20010315\"></{$p}CanonicalizationMethod>" .
        "<{$p}SignatureMethod Algorithm=\"http://www.w3.org/2000/09/xmldsig#rsa-sha1\"></{$p}SignatureMethod>" .
        "<{$p}Reference URI=\"{$uriRef}\">" .
        "<{$p}Transforms>" .
        "<{$p}Transform Algorithm=\"http://www.w3.org/2000/09/xmldsig#enveloped-signature\"></{$p}Transform>" .
        "<{$p}Transform Algorithm=\"http://www.w3.org/TR/2001/REC-xml-c14n-20010315\"></{$p}Transform>" .
        "</{$p}Transforms>" .
        "<{$p}DigestMethod Algorithm=\"http://www.w3.org/2000/09/xmldsig#sha1\"></{$p}DigestMethod>" .
        "<{$p}DigestValue>{$digestValue}</{$p}DigestValue>" .
        "</{$p}Reference>" .
        "</{$p}SignedInfo>";

    $domSignedInfo = new DOMDocument();
    $domSignedInfo->loadXML($signedInfo);
    $canonicalSignedInfo = $domSignedInfo->C14N(false, false, null, null);

    $signatureValue = '';
    openssl_sign($canonicalSignedInfo, $signatureValue, $certs['pkey'], OPENSSL_ALGO_SHA1);
    $signatureValueContent = base64_encode($signatureValue);
    $signatureValueContent = chunk_split($signatureValueContent, 76, "\n");

    $x509 = str_replace(['-----BEGIN CERTIFICATE-----', '-----END CERTIFICATE-----', "\r", "\n"], '', $certs['cert']);

    $signatureXml = "<{$p}Signature{$nsDecl}>" .
        $canonicalSignedInfo .
        "<{$p}SignatureValue>{$signatureValueContent}</{$p}SignatureValue>" .
        "<{$p}KeyInfo>" .
        "<{$p}X509Data>" .
        "<{$p}X509Certificate>{$x509}</{$p}X509Certificate>" .
        "</{$p}X509Data>" .
        "</{$p}KeyInfo>" .
        "</{$p}Signature>";

    $signatureFragment = $dom->createDocumentFragment();
    $signatureFragment->appendXML($signatureXml);
    $dom->documentElement->appendChild($signatureFragment);

    // Clean headers from result
    $finalXml = $dom->saveXML();
    $search1 = '<' . '?xml version="1.0" encoding="UTF-8"?' . '>';
    $search2 = '<' . '?xml version="1.0"?' . '>';
    $finalXml = str_replace($search1, '', $finalXml);
    $finalXml = str_replace($search2, '', $finalXml);
    return trim($finalXml);
}

function sendSoap($finalXmlPayload, $endpoint_url, $certsA1 = [], $variation = 'standard')
{
    $xmlDecl = '<' . '?xml version="1.0" encoding="UTF-8"?' . '>';

    // CDATA Strategy
    if ($variation === 'no_cdata') {
        // HTML Entities instead of CDATA
        $payloadForEnvelope = htmlspecialchars($finalXmlPayload, ENT_XML1, 'UTF-8');
        $cabecMsg = htmlspecialchars("$xmlDecl<cabecalho versao=\"1.00\" xmlns=\"http://www.abrasf.org.br/nfse.xsd\"><versaoDados>2.04</versaoDados></cabecalho>", ENT_XML1, 'UTF-8');

        $soapEnvelope = <<<XML
<soap:Envelope xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xmlns:xsd="http://www.w3.org/2001/XMLSchema" xmlns:soap="http://schemas.xmlsoap.org/soap/envelope/">
  <soap:Body>
    <ConsultarNfseServicoPrestado xmlns="http://nfse.abrasf.org.br">
      <nfseCabecMsg>$cabecMsg</nfseCabecMsg>
      <nfseDadosMsg>$payloadForEnvelope</nfseDadosMsg>
    </ConsultarNfseServicoPrestado>
  </soap:Body>
</soap:Envelope>
XML;
    } else {
        // Standard CDATA
        $soapEnvelope = <<<XML
<soap:Envelope xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xmlns:xsd="http://www.w3.org/2001/XMLSchema" xmlns:soap="http://schemas.xmlsoap.org/soap/envelope/">
  <soap:Body>
    <ConsultarNfseServicoPrestado xmlns="http://nfse.abrasf.org.br">
      <nfseCabecMsg><![CDATA[$xmlDecl<cabecalho versao="1.00" xmlns="http://www.abrasf.org.br/nfse.xsd"><versaoDados>2.04</versaoDados></cabecalho>]]></nfseCabecMsg>
      <nfseDadosMsg><![CDATA[$finalXmlPayload]]></nfseDadosMsg>
    </ConsultarNfseServicoPrestado>
  </soap:Body>
</soap:Envelope>
XML;
    }

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $endpoint_url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $soapEnvelope);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: text/xml; charset=utf-8',
        'SOAPAction: "http://nfse.abrasf.org.br/ConsultarNfseServicoPrestado"',
        'Content-Length: ' . strlen($soapEnvelope),
        'Accept: text/xml',
        'Accept-Language: pt-BR',
        'Connection: keep-alive'
    ]);
    curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64)');
    curl_setopt($ch, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_1);

    if (!empty($certsA1)) {
        $certPemFile = tempnam(sys_get_temp_dir(), 'cert');
        $keyPemFile = tempnam(sys_get_temp_dir(), 'key');
        file_put_contents($certPemFile, $certsA1['cert']);
        file_put_contents($keyPemFile, $certsA1['pkey']);
        curl_setopt($ch, CURLOPT_SSLCERT, $certPemFile);
        curl_setopt($ch, CURLOPT_SSLKEY, $keyPemFile);
    }

    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
    curl_setopt($ch, CURLOPT_VERBOSE, true);

    $responseHeaders = [];
    curl_setopt($ch, CURLOPT_HEADERFUNCTION, function ($curl, $header) use (&$responseHeaders) {
        if (trim($header) !== '')
            $responseHeaders[] = trim($header);
        return strlen($header);
    });

    $responseBody = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError = curl_error($ch);

    curl_close($ch);
    if (!empty($certsA1)) {
        @unlink($certPemFile);
        @unlink($keyPemFile);
    }

    echo json_encode([
        'status' => $httpCode == 200 ? 'success' : 'fail',
        'http_code' => $httpCode,
        'endpoint' => $endpoint_url,
        'headers' => $responseHeaders,
        'response_body' => $responseBody,
        'request_envelope' => $soapEnvelope,
        'curl_error' => $curlError
    ]);
}
?>