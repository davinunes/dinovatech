<?php
header('Content-Type: application/json');

$input = json_decode(file_get_contents('php://input'), true);
if (!$input) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid JSON']);
    exit;
}

$action = $input['action'] ?? 'direct_a1';

// Configuration
$endpoint_type = $input['endpoint'] ?? 'fictitious';
$endpoint_url = ($endpoint_type === 'official')
    ? 'https://www.issnetonline.com.br/apresentacao/df/webservicenfse204/nfse.asmx'
    : 'https://www.issnetonline.com.br/homologaabrasf/webservicenfse204/nfse.asmx';

$certificado_pfx = __DIR__ . '/../certificado/DInovaTech_1001347811.pfx';
$senha_arquivo = __DIR__ . '/../certificado/certificado.php';

// --- ACTION 1: DIRECT A1 SEND (Server Signs) ---
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

    // Sign
    $signedXml = assinarRoot($rootXml, $certs, "#" . $rootId);

    // Send
    sendSoap($signedXml, $endpoint_url, $certs);
    exit;
}

// --- ACTION 2: PREPARE HASH (For A3 Client Signing) ---
if ($action === 'prepare_hash') {
    $xmlComponents = buildPedidoXml($input);
    $rootXml = $xmlComponents['root'];
    $rootId = $xmlComponents['id'];

    // 1. Digest of Root (for Reference)
    $dom = new DOMDocument('1.0', 'UTF-8');
    $dom->loadXML($rootXml);
    $canonicalizedRoot = $dom->C14N(false, false, null, null);
    $digestValueRoot = base64_encode(sha1($canonicalizedRoot, true)); // SHA1

    // 2. Build SignedInfo
    // Note: We MUST use the same structure as we will use to re-assemble
    $signedInfo = '<ds:SignedInfo xmlns:ds="http://www.w3.org/2000/09/xmldsig#">' .
        '<ds:CanonicalizationMethod Algorithm="http://www.w3.org/TR/2001/REC-xml-c14n-20010315"></ds:CanonicalizationMethod>' .
        '<ds:SignatureMethod Algorithm="http://www.w3.org/2000/09/xmldsig#rsa-sha1"></ds:SignatureMethod>' .
        '<ds:Reference URI="#' . $rootId . '">' .
        '<ds:Transforms>' .
        '<ds:Transform Algorithm="http://www.w3.org/2000/09/xmldsig#enveloped-signature"></ds:Transform>' .
        '<ds:Transform Algorithm="http://www.w3.org/TR/2001/REC-xml-c14n-20010315"></ds:Transform>' .
        '</ds:Transforms>' .
        '<ds:DigestMethod Algorithm="http://www.w3.org/2000/09/xmldsig#sha1"></ds:DigestMethod>' .
        '<ds:DigestValue>' . $digestValueRoot . '</ds:DigestValue>' .
        '</ds:Reference>' .
        '</ds:SignedInfo>';

    // 3. Hash of SignedInfo (To sign with Private Key)
    $domSI = new DOMDocument();
    $domSI->loadXML($signedInfo);
    $canonicalSI = $domSI->C14N(false, false, null, null);
    $toSignHash = sha1($canonicalSI, true); // Raw binary hash

    echo json_encode([
        'status' => 'success',
        'hash_to_sign_b64' => base64_encode($toSignHash), // Send as B64 to JS
        'digest_algorithm' => 'SHA-1',
        'signed_info_xml' => $signedInfo, // Need this to reconstruct
        'original_xml' => $rootXml, // Need this to reconstruct
        'canonical_si' => $canonicalSI // Debug
    ]);
    exit;
}

// --- ACTION 3: SEND SIGNED (Assemble and Send) ---
if ($action === 'send_signed') {
    $signatureB64 = $input['signature_b64'];
    $certB64 = $input['cert_b64']; // X509 plain
    $signedInfoXml = $input['signed_info_xml'];
    $rootXml = $input['original_xml'];

    // Assemble Signature Block
    // We already have the SignedInfo XML that was hashed.
    // We assume the client signed the hash of that EXACT XML.

    // Chunk split signature for XML standard
    $signatureValueContent = chunk_split($signatureB64, 76, "\n");
    $x509Content = $certB64; // Client usually sends raw base64

    // We need to verify if SignedInfo needs to be C14N again? 
    // It is already XML string. We inject it.
    // BUT DOMDocument parsing might change it. Ideally we inject the string directly or import node.

    $dom = new DOMDocument('1.0', 'UTF-8');
    $dom->loadXML($rootXml);

    // Re-Canonicalize SignedInfo to ensure we inject exactly what was signed?
    // Actually we just construct the Signature element.
    $domSI = new DOMDocument();
    $domSI->loadXML($signedInfoXml);
    $canonSignedInfo = $domSI->C14N(false, false, null, null); // Ensure canonical form in final XML

    $signatureXml = '<ds:Signature xmlns:ds="http://www.w3.org/2000/09/xmldsig#">' .
        $canonSignedInfo .
        '<ds:SignatureValue>' . $signatureValueContent . '</ds:SignatureValue>' .
        '<ds:KeyInfo>' .
        '<ds:X509Data>' .
        '<ds:X509Certificate>' . $x509Content . '</ds:X509Certificate>' .
        '</ds:X509Data>' .
        '</ds:KeyInfo>' .
        '</ds:Signature>';

    $signatureFragment = $dom->createDocumentFragment();
    $signatureFragment->appendXML($signatureXml);
    $dom->documentElement->appendChild($signatureFragment);

    $finalXml = $dom->saveXML();

    // Remove headers
    $search1 = '<' . '?xml version="1.0" encoding="UTF-8"?' . '>';
    $search2 = '<' . '?xml version="1.0"?' . '>';
    $finalXml = str_replace($search1, '', $finalXml);
    $finalXml = str_replace($search2, '', $finalXml);
    $finalXml = trim($finalXml);

    // Try to load A1 cert for Transport Layer if available, else standard
    $certsA1 = [];
    if (file_exists($certificado_pfx)) {
        require $senha_arquivo;
        $pfx = file_get_contents($certificado_pfx);
        openssl_pkcs12_read($pfx, $certsA1, $senhaCertificado);
    }

    sendSoap($finalXml, $endpoint_url, $certsA1);
    exit;
}


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

function assinarRoot($xmlString, $certs, $uriRef)
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

function sendSoap($finalXmlPayload, $endpoint_url, $certsA1 = [])
{
    $xmlDecl = '<' . '?xml version="1.0" encoding="UTF-8"?' . '>';
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