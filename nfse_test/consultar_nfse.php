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

// Step 1: Create the Inner XML (Pedido)
// Note: We are not adding 'Id' attribute because XSD schema for ConsultarNfseServicoPrestadoEnvio -> Pedido doesn't specify one.
// We will sign the entire empty-URI reference.
$pedidoXml = <<<XML
<Pedido xmlns="http://nfse.abrasf.org.br">
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

// Step 2: Sign the Pedido (XML Signature)
$signedXml = assinarId($pedidoXml, $certs);

// Step 3: Wrap correctly
$xmlEnvio = "<ConsultarNfseServicoPrestadoEnvio xmlns=\"http://nfse.abrasf.org.br\">" . $signedXml . "</ConsultarNfseServicoPrestadoEnvio>";

// Step 4: SOAP Envelope
$soapEnvelope = <<<XML
<soap:Envelope xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xmlns:xsd="http://www.w3.org/2001/XMLSchema" xmlns:soap="http://schemas.xmlsoap.org/soap/envelope/">
  <soap:Body>
    <ConsultarNfseServicoPrestado xmlns="http://nfse.abrasf.org.br">
      <nfseCabecMsg><![CDATA[<?xml version="1.0" encoding="UTF-8"?><cabecalho versao="2.04" xmlns="http://nfse.abrasf.org.br"><versaoDados>2.04</versaoDados></cabecalho>]]></nfseCabecMsg>
      <nfseDadosMsg><![CDATA[$xmlEnvio]]></nfseDadosMsg>
    </ConsultarNfseServicoPrestado>
  </soap:Body>
</soap:Envelope>
XML;

echo "Sending SOAP Request to $endpoint_homolog ...\n";

// Save temporary PEM files for cURL
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
    'SOAPAction: "http://nfse.abrasf.org.br/ConsultarNfseServicoPrestado"',
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


// Helper function to Sign the XML
function assinarId($xmlString, $certs)
{
    $dom = new DOMDocument('1.0', 'UTF-8');
    $dom->loadXML($xmlString);

    // We assume the root element is what we want to sign.
    // Since Pedido has no ID in this XSD context, we will sign the whole document (URI="")
    // BUT we need to check if we can add an ID. Usually ABRASF schemas DON'T allow arbitrary attributes unless 'anyAttribute' is present.
    // The XSD checked earlier DOES NOT show anyAttribute on Pedido.
    // So we must use an empty URI reference <Reference URI=""> which means "the containing resource".
    // However, when embedding the signature INSIDE the element (Enveloped Signature), URI="" refers to the document containing the signature.

    // Canonicalize the document (C14N)
    $canonicalized = $dom->C14N(false, false, null, null);

    // Calculate Digest (SHA1)
    $digestValue = base64_encode(sha1($canonicalized, true));

    // Prepare SignedInfo
    // Note: We use the namespace prefixes strictly as required by typically strict servers
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

    // Canonicalize SignedInfo for signing
    $signedInfoDom = new DOMDocument('1.0', 'UTF-8');
    $signedInfoDom->loadXML($signedInfo);
    $canonicalSignedInfo = $signedInfoDom->C14N(false, false, null, null);

    // Sign using Private Key
    $signatureValue = '';
    openssl_sign($canonicalSignedInfo, $signatureValue, $certs['pkey'], OPENSSL_ALGO_SHA1);
    $signatureValue = base64_encode($signatureValue);

    // Get X509 Certificate (clean headers)
    $x509 = str_replace(['-----BEGIN CERTIFICATE-----', '-----END CERTIFICATE-----', "\r", "\n"], '', $certs['cert']);

    // Construct valid Signature Block
    $signatureXml = <<<XML
<Signature xmlns="http://www.w3.org/2000/09/xmldsig#">
$canonicalSignedInfo
<SignatureValue>$signatureValue</SignatureValue>
<KeyInfo>
<X509Data>
<X509Certificate>$x509</X509Certificate>
</X509Data>
</KeyInfo>
</Signature>
XML;

    // Append Signature to the root element
    $signatureFragment = $dom->createDocumentFragment();
    $signatureFragment->appendXML($signatureXml);
    $dom->documentElement->appendChild($signatureFragment);

    return $dom->saveXML($dom->documentElement);
}
?>