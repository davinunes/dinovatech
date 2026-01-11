<?php
header('Content-Type: application/json');

// Helper to get input
$input = json_decode(file_get_contents('php://input'), true);

if (!$input) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid JSON input']);
    exit;
}

// Configuration
$endpoint_type = $input['endpoint'] ?? 'fictitious';
if ($endpoint_type === 'official') {
    $endpoint_url = 'https://www.issnetonline.com.br/apresentacao/df/webservicenfse204/nfse.asmx';
} else {
    $endpoint_url = 'https://www.issnetonline.com.br/homologaabrasf/webservicenfse204/nfse.asmx';
}

$certificado_pfx = __DIR__ . '/../certificado/DInovaTech_1001347811.pfx';
$senha_arquivo = __DIR__ . '/../certificado/certificado.php';

// Check if we are in A3 mode (Pre-signed XML provided) or A1 mode
$signedXml = $input['xml_signed'] ?? null;
$certs = [];

// For A1, we need to load the cert to sign.
// For A3, we usually still need a CLIENT CERT for SSL (Mutual TLS).
// If the user uses A3, we technically can't do Mutual TLS from the server using the A3 cert (it's on the client).
// However, the "Fictitious" environment often implies HTTP or doesn't mandate Client Auth if the message is signed.
// OR, we can use the A1 cert just for the Transport Layer (Tunnel) and the A3 cert for the Message Layer (Signature).
// Let's assume we use the A1 cert for transport if available, or try without if not.

if (file_exists($certificado_pfx)) {
    require $senha_arquivo;
    $pfxContent = file_get_contents($certificado_pfx);
    openssl_pkcs12_read($pfxContent, $certs, $senhaCertificado);
} else if (!$signedXml) {
    echo json_encode(['status' => 'error', 'message' => "Certificate not found at $certificado_pfx and no signed XML provided."]);
    exit;
}


if (!$signedXml) {
    // --- MODE A1: SERVER SIDE SIGNING ---

    // Parameters
    $cnpj = $input['cnpj'] ?? '';
    $cpf = $input['cpf'] ?? '';
    $im = $input['im'] ?? '';
    $numero = $input['numero'] ?? '';
    $dataInicial = $input['dataInicial'] ?? '';
    $dataFinal = $input['dataFinal'] ?? '';

    // Build Pedido
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

    // Construct Root
    // The previous manual construction was fine.
    // For consistency with WebPKI, we might want to ensure the template matches what frontend generates.

    $rootId = "ConsultarNfseServicoPrestadoEnvio";
    $rootXml = '<ConsultarNfseServicoPrestadoEnvio xmlns="http://www.abrasf.org.br/nfse.xsd" Id="' . $rootId . '">' . $pedidoContent . '</ConsultarNfseServicoPrestadoEnvio>';

    $signedXml = assinarRoot($rootXml, $certs, "#" . $rootId);
}

// Remove headers
$search1 = '<' . '?xml version="1.0" encoding="UTF-8"?' . '>';
$search2 = '<' . '?xml version="1.0"?' . '>';
$signedXml = str_replace($search1, '', $signedXml);
$signedXml = str_replace($search2, '', $signedXml);
$signedXml = trim($signedXml);

// Envelope
$xmlDecl = '<' . '?xml version="1.0" encoding="UTF-8"?' . '>';
$soapEnvelope = <<<XML
<soap:Envelope xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xmlns:xsd="http://www.w3.org/2001/XMLSchema" xmlns:soap="http://schemas.xmlsoap.org/soap/envelope/">
  <soap:Body>
    <ConsultarNfseServicoPrestado xmlns="http://nfse.abrasf.org.br">
      <nfseCabecMsg><![CDATA[$xmlDecl<cabecalho versao="1.00" xmlns="http://www.abrasf.org.br/nfse.xsd"><versaoDados>2.04</versaoDados></cabecalho>]]></nfseCabecMsg>
      <nfseDadosMsg><![CDATA[$signedXml]]></nfseDadosMsg>
    </ConsultarNfseServicoPrestado>
  </soap:Body>
</soap:Envelope>
XML;

// Request
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

// Client Auth (Transport) - Use A1 cert if available
if (!empty($certs)) {
    $certPemFile = tempnam(sys_get_temp_dir(), 'cert');
    $keyPemFile = tempnam(sys_get_temp_dir(), 'key');
    file_put_contents($certPemFile, $certs['cert']);
    file_put_contents($keyPemFile, $certs['pkey']);

    curl_setopt($ch, CURLOPT_SSLCERT, $certPemFile);
    curl_setopt($ch, CURLOPT_SSLKEY, $keyPemFile);
}

curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
curl_setopt($ch, CURLOPT_VERBOSE, true);

$responseHeaders = [];
curl_setopt($ch, CURLOPT_HEADERFUNCTION, function ($curl, $header) use (&$responseHeaders) {
    if (trim($header) !== '') {
        $responseHeaders[] = trim($header);
    }
    return strlen($header);
});

$responseBody = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$curlError = curl_error($ch);

curl_close($ch);
if (!empty($certs)) {
    @unlink($certPemFile);
    @unlink($keyPemFile);
}

// Return JSON
echo json_encode([
    'status' => $httpCode == 200 ? 'success' : 'fail',
    'http_code' => $httpCode,
    'endpoint' => $endpoint_url,
    'headers' => $responseHeaders,
    'response_body' => $responseBody,
    'request_envelope' => $soapEnvelope,
    'curl_error' => $curlError
]);

// Signing Function (A1)
function assinarRoot($xmlString, $certs, $uriRef = "")
{
    $dom = new DOMDocument('1.0', 'UTF-8');
    $dom->loadXML($xmlString);

    $canonicalized = $dom->C14N(false, false, null, null);
    $digestValue = base64_encode(sha1($canonicalized, true));

    $signedInfo = '<ds:SignedInfo xmlns:ds="http://www.w3.org/2000/09/xmldsig#">' .
        '<ds:CanonicalizationMethod Algorithm="http://www.w3.org/TR/2001/REC-xml-c14n-20010315"></ds:CanonicalizationMethod>' .
        '<ds:SignatureMethod Algorithm="http://www.w3.org/2000/09/xmldsig#rsa-sha1"></ds:SignatureMethod>' .
        '<ds:Reference URI="' . $uriRef . '">' .
        '<ds:Transforms>' .
        '<ds:Transform Algorithm="http://www.w3.org/2000/09/xmldsig#enveloped-signature"></ds:Transform>' .
        '<ds:Transform Algorithm="http://www.w3.org/TR/2001/REC-xml-c14n-20010315"></ds:Transform>' .
        '</ds:Transforms>' .
        '<ds:DigestMethod Algorithm="http://www.w3.org/2000/09/xmldsig#sha1"></ds:DigestMethod>' .
        '<ds:DigestValue>' . $digestValue . '</ds:DigestValue>' .
        '</ds:Reference>' .
        '</ds:SignedInfo>';

    $domSignedInfo = new DOMDocument();
    $domSignedInfo->loadXML($signedInfo);
    $canonicalSignedInfo = $domSignedInfo->C14N(false, false, null, null);

    $signatureValue = '';
    openssl_sign($canonicalSignedInfo, $signatureValue, $certs['pkey'], OPENSSL_ALGO_SHA1);
    $signatureValueContent = base64_encode($signatureValue);
    $signatureValueContent = chunk_split($signatureValueContent, 76, "\n");

    $x509 = str_replace(['-----BEGIN CERTIFICATE-----', '-----END CERTIFICATE-----', "\r", "\n"], '', $certs['cert']);

    $signatureXml = '<ds:Signature xmlns:ds="http://www.w3.org/2000/09/xmldsig#">' .
        $canonicalSignedInfo .
        '<ds:SignatureValue>' . $signatureValueContent . '</ds:SignatureValue>' .
        '<ds:KeyInfo>' .
        '<ds:X509Data>' .
        '<ds:X509Certificate>' . $x509 . '</ds:X509Certificate>' .
        '</ds:X509Data>' .
        '</ds:KeyInfo>' .
        '</ds:Signature>';

    $signatureFragment = $dom->createDocumentFragment();
    $signatureFragment->appendXML($signatureXml);
    $dom->documentElement->appendChild($signatureFragment);

    return $dom->saveXML();
}
?>