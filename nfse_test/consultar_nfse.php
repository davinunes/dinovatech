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
// Correct Namespace: http://nfse.abrasf.org.br
// STRUCTURE: ConsultarNfseServicoPrestadoEnvio -> Pedido, Signature
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
// IMPORTANT: We need to sign content, but typically the signature is enveloped or detached.
// XSD says: Sequence { Pedido, Signature }. This usually means "Detached" or "Enveloping" depending on URI.
// If URI="", it signs the whole doc relative to Signature.
// If Signature is a sibling, it cannot sign the parent without ID.
// However, standard ABRASF 2.04 often uses Enveloped signature where Signature is INSIDE the root.
// Wait, my XSD check showed Signature as SIBLING of Pedido.
// <ConsultarNfseServicoPrestadoEnvio> -> Sequence { Pedido, Signature }
// In this case, Signature typically signs 'Pedido'. To do that, Pedido needs an ID.
// But XSD for Pedido has no optional ID attribute?
// Let's assume the Signature must be *Enveloped* by the main element, but physically placed after Pedido?
// No, Enveloped means Signature is a child of the signed element.
// If valid structure is <Envio><Pedido/><Signature/></Envio>, then Signature is partial.
// It usually signs #Pedido.
// Since we don't have ID on Pedido, we might try signing the whole Envio, but that creates circular ref if Enveloped.
// Let's try signing the 'Pedido' content by value? No, XMLSig signs by Reference URI.
// Maybe the server expects the Signature to be INSIDE Pedido despite my reading?
// No, the XSD was clear: element 'Pedido', then element 'Signature'.
// Let's try to add an ID to Pedido manually even if schema strictly doesn't show it (sometimes it's allowed by anyAttribute).
// OR, common pattern: URI="" signs the containing document key.
// Let's construct the "SignedInfo" to reference the Pedido if possible.
// For now, I will construct the <ConsultarNfseServicoPrestadoEnvio> containing Pedido, and then append the Signature.

// To sign 'Pedido', we need to canonicalize IT.
$dom = new DOMDocument('1.0', 'UTF-8');
$dom->loadXML($pedidoXml);
$canonicalPedido = $dom->C14N(false, false, null, null);
$digestValue = base64_encode(sha1($canonicalPedido, true));

// We will simulate a Reference to the Pedido logic, but without URI (or URI="").
// If URI="", it validates the whole root.
// If the server validates "ConsultarNfseServicoPrestadoEnvio", then URI="" works if Signature is child of it.
// Let's build the Signature block detached/sibling but referenced.
// Actually, if I cannot put ID, I cannot reference it specifically by URI="#id".
// Let's assume URI="" and the Signature is a child of EnvÃo.

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

// Recalculate digest? No, URI="" means "the document containing this signature".
// So digest must be of the "ConsultarNfseServicoPrestadoEnvio" content *excluding* the Signature itself.
// This is circular.
// Approach 2: Sign the 'Pedido' explicitly and add Id in PHP even if XSD didn't explicitly show it (it often inherits attributes).
$pedidoXmlWithId = str_replace('<Pedido', '<Pedido Id="pedido1"', $pedidoXml);
$domPedido = new DOMDocument();
$domPedido->loadXML($pedidoXmlWithId);
$canonicalPedido = $domPedido->C14N(false, false, null, null);
$digestValue = base64_encode(sha1($canonicalPedido, true));

$signedInfo = <<<XML
<SignedInfo xmlns="http://www.w3.org/2000/09/xmldsig#">
<CanonicalizationMethod Algorithm="http://www.w3.org/TR/2001/REC-xml-c14n-20010315"></CanonicalizationMethod>
<SignatureMethod Algorithm="http://www.w3.org/2000/09/xmldsig#rsa-sha1"></SignatureMethod>
<Reference URI="#pedido1">
<Transforms>
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

// Final assembly: Envio -> Pedido + Signature
$xmlEnvio = "<ConsultarNfseServicoPrestadoEnvio xmlns=\"http://nfse.abrasf.org.br\">" . $pedidoXmlWithId . $signatureXml . "</ConsultarNfseServicoPrestadoEnvio>";
// Remove the XML Declaration from internal parts if any

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
?>