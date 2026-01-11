<?php

// Basic configuration
// URL 1: Homologação Dados Oficiais (Blocked by Cloudflare)
// $wsdl_homolog = 'https://www.issnetonline.com.br/apresentacao/df/webservicenfse204/nfse.asmx?wsdl';

// URL 2: Homologação Fictícia (Bypass WAF)
$endpoint_homolog = 'https://www.issnetonline.com.br/homologaabrasf/webservicenfse204/nfse.asmx';

$certificado_pfx = __DIR__ . '/../certificado/DInovaTech_1001347811.pfx';

// Load password from external file
require __DIR__ . '/../certificado/certificado.php';

if (!file_exists($certificado_pfx)) {
    die("Error: Certificate file not found at $certificado_pfx\n");
}

$pfxContent = file_get_contents($certificado_pfx);
$certs = [];
if (!openssl_pkcs12_read($pfxContent, $certs, $senhaCertificado)) {
    die("Error requesting certificate. Check the password.\n");
}

// --- XML Construction ---
$cnpj = '61733714000101';
$inscricaoMunicipal = '0841147200111';
$numeroNota = '1';

// NAMESPACE STRATEGY:
// Inner XML (Payload): http://www.abrasf.org.br/nfse.xsd
// Outer XML (SOAP Action): http://nfse.abrasf.org.br
// Header Version: 1.00 (per cabecalho.xml) NOT 2.04

$pedidoContent = <<<XML
<Pedido>
    <Prestador>
        <CpfCnpj>
            <Cnpj>$cnpj</Cnpj>
        </CpfCnpj>
        <InscricaoMunicipal>$inscricaoMunicipal</InscricaoMunicipal>
    </Prestador>
    <NumeroNfse>$numeroNota</NumeroNfse>
    <Pagina>1</Pagina>
</Pedido>
XML;

// Structure to be signed (Root Element)
$rootXml = '<ConsultarNfseServicoPrestadoEnvio xmlns="http://www.abrasf.org.br/nfse.xsd">' . $pedidoContent . '</ConsultarNfseServicoPrestadoEnvio>';

// Sign the Root Element
$signedXml = assinarRoot($rootXml, $certs);

// Remove XML Declaration from Signed XML if present, because CDATA + <?xml?> inside Body is risky
// Splitting string to avoid short_open_tag issues on server
$search1 = '<' . '?xml version="1.0" encoding="UTF-8"?' . '>';
$search2 = '<' . '?xml version="1.0"?' . '>';
$signedXml = str_replace($search1, '', $signedXml);
$signedXml = str_replace($search2, '', $signedXml);
$signedXml = trim($signedXml);

// SOAP Envelope
// Note 'versao="1.00"' in cabecalho, matching the sample file found.
$soapEnvelope = <<<XML
<soap:Envelope xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xmlns:xsd="http://www.w3.org/2001/XMLSchema" xmlns:soap="http://schemas.xmlsoap.org/soap/envelope/">
  <soap:Body>
    <ConsultarNfseServicoPrestado xmlns="http://nfse.abrasf.org.br">
      <nfseCabecMsg><![CDATA[<?xml version="1.0" encoding="UTF-8"?><cabecalho versao="1.00" xmlns="http://www.abrasf.org.br/nfse.xsd"><versaoDados>2.04</versaoDados></cabecalho>]]></nfseCabecMsg>
      <nfseDadosMsg><![CDATA[$signedXml]]></nfseDadosMsg>
    </ConsultarNfseServicoPrestado>
  </soap:Body>
</soap:Envelope>
XML;

echo "Sending SOAP Request to $endpoint_homolog ...\n";

$certPemFile = tempnam(sys_get_temp_dir(), 'cert');
$keyPemFile = tempnam(sys_get_temp_dir(), 'key');
file_put_contents($certPemFile, $certs['cert']);
file_put_contents($keyPemFile, $certs['pkey']);

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $endpoint_homolog);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, $soapEnvelope);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: text/xml; charset=utf-8',
    'SOAPAction: "http://nfse.abrasf.org.br/ConsultarNfseServicoPrestado"', // Service Namespace
    'Content-Length: ' . strlen($soapEnvelope)
]);
curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64)');
curl_setopt($ch, CURLOPT_SSLCERT, $certPemFile);
curl_setopt($ch, CURLOPT_SSLKEY, $keyPemFile);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);

$response = curl_exec($ch);

if (curl_errno($ch)) {
    echo 'Curl Error: ' . curl_error($ch) . "\n";
} else {
    echo "Response received:\n";
    $dom = new DOMDocument;
    $dom->preserveWhiteSpace = false;
    $dom->formatOutput = true;
    if (@$dom->loadXML($response)) {
        echo $dom->saveXML();
    } else {
        echo $response;
    }
}

curl_close($ch);
unlink($certPemFile);
unlink($keyPemFile);


function assinarRoot($xmlString, $certs) {
    $dom = new DOMDocument('1.0', 'UTF-8');
    $dom->loadXML($xmlString);
    
    // Canonicalize
    $canonicalized = $dom->C14N(false, false, null, null);
    $digestValue = base64_encode(sha1($canonicalized, true));
    
    $signedInfo = <<<XML
<SignedInfo xmlns="http://www.w3.org/2000/09/xmldsig#">
<CanonicalizationMethod Algorithm="http://www.w3.org/TR/2001/REC-xml-c14n-20010315"></CanonicalizationMethod>
<SignatureMethod Algorithm="http://www.w3.org/2000/09/xmldsig#rsa-sha1"></SignatureMethod>
<Reference URI="">
<Transforms>
<Transform Algorithm="http://www.w3.org/2000/09/xmldsig#enveloped-signature"></Transform>
<Transform Algorithm="http://www.w3.org/TR/2001/REC-xml-c14n-20010315"></Transform>
</Transforms>
<DigestMethod Algorithm="http://www.w3.org/2000/09/xmldsig#sha1"></DigestMethod>
<DigestValue>$digestValue</DigestValue>
</Reference>
</SignedInfo>
XML;

    $domSignedInfo = new DOMDocument();
    $domSignedInfo->loadXML($signedInfo);
    $canonicalSignedInfo = $domSignedInfo->C14N(false, false, null, null);
    
    $signatureValue = '';
    openssl_sign($canonicalSignedInfo, $signatureValue, $certs['pkey'], OPENSSL_ALGO_SHA1);
    $signatureValueContent = base64_encode($signatureValue);
    
    $x509 = str_replace(['-----BEGIN CERTIFICATE-----', '-----END CERTIFICATE-----', "\r", "\n"], '', $certs['cert']);
    
    $signatureXml = <<<XML
<Signature xmlns="http://www.w3.org/2000/09/xmldsig#">
$canonicalSignedInfo
<SignatureValue>$signatureValueContent</SignatureValue>
<KeyInfo>
<X509Data>
<X509Certificate>$x509</X509Certificate>
</X509Data>
</KeyInfo>
</Signature>
XML;

    // Append Signature to Root
    $signatureFragment = $dom->createDocumentFragment();
    $signatureFragment->appendXML($signatureXml);
    $dom->documentElement->appendChild($signatureFragment);
    
    return $dom->saveXML();
}
?>