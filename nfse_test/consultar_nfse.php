<?php

// Basic configuration
$wsdl_homolog = 'https://www.issnetonline.com.br/apresentacao/df/webservicenfse204/nfse.asmx?wsdl';
$endpoint_homolog = 'https://www.issnetonline.com.br/apresentacao/df/webservicenfse204/nfse.asmx';
$certificado_pfx = __DIR__ . '/../certificado/DInovaTech_1001347811.pfx';

// Load password from external file
require __DIR__ . '/../certificado/certificado.php';
// $senhaCertificado comes from the require above

// Verify if certificate exists
if (!file_exists($certificado_pfx)) {
    die("Error: Certificate file not found at $certificado_pfx\n");
}

// Read the Certificate
$pfxContent = file_get_contents($certificado_pfx);
$certs = [];
if (!openssl_pkcs12_read($pfxContent, $certs, $senhaCertificado)) {
    die("Error requesting certificate. Check the password.\n");
}

$certData = openssl_x509_parse($certs['cert']);
echo "Certificate Loaded: " . $certData['subject']['CN'] . "\n";
echo "Valid until: " . date('Y-m-d H:i:s', $certData['validTo_time_t']) . "\n\n";

// --- XML Construction ---

// Data from your XML example
$cnpj = '61733714000101';
$inscricaoMunicipal = '0841147200111';
$numeroNota = '1';
$dataCompetencia = '2026-01-11'; // Using the date from your XML

// XML Structure for ConsultarNfseServicoPrestadoEnvio (ABRASF 2.04)
// Note: Some cities require signing the 'Pedido', others don't for consultation.
// We will start without signing the XML body, but using the Certificate for SSL Auth.
// If it fails requiring signature, we will implement the signer.
// ABRASF 2.04 usually asks for signature in 'ConsultarNfseServicoPrestadoEnvio'.

$xmlEnvio = <<<XML
<ConsultarNfseServicoPrestadoEnvio xmlns="http://www.abrasf.org.br/nfse.xsd">
    <Prestador>
        <CpfCnpj>
            <Cnpj>$cnpj</Cnpj>
        </CpfCnpj>
        <InscricaoMunicipal>$inscricaoMunicipal</InscricaoMunicipal>
    </Prestador>
    <NumeroNfse>$numeroNota</NumeroNfse>
    <PeriodoCompetencia>
        <DataInicial>$dataCompetencia</DataInicial>
        <DataFinal>$dataCompetencia</DataFinal>
    </PeriodoCompetencia>
</ConsultarNfseServicoPrestadoEnvio>
XML;

// Construct the SOAP Envelope manually to fully control the structure
$soapEnvelope = <<<XML
<soap:Envelope xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xmlns:xsd="http://www.w3.org/2001/XMLSchema" xmlns:soap="http://schemas.xmlsoap.org/soap/envelope/">
  <soap:Body>
    <ConsultarNfseServicoPrestado xmlns="http://www.abrasf.org.br/nfse.xsd">
      <nfseCabecMsg><![CDATA[<?xml version="1.0" encoding="UTF-8"?><cabecalho versao="2.04" xmlns="http://www.abrasf.org.br/nfse.xsd"><versaoDados>2.04</versaoDados></cabecalho>]]></nfseCabecMsg>
      <nfseDadosMsg><![CDATA[$xmlEnvio]]></nfseDadosMsg>
    </ConsultarNfseServicoPrestado>
  </soap:Body>
</soap:Envelope>
XML;

echo "Sending SOAP Request...\n";

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
    'SOAPAction: "http://www.abrasf.org.br/nfse.xsd/ConsultarNfseServicoPrestado"',
    'Content-Length: ' . strlen($soapEnvelope)
]);

// Client Certificate Settings
curl_setopt($ch, CURLOPT_SSLCERT, $certPemFile);
curl_setopt($ch, CURLOPT_SSLKEY, $keyPemFile);
// If key is encrypted, provide password again? Usually openssl_pkcs12_read decrypts it into pkey string.
// If pkey string is unencrypted, no pass needed for curl.
// If pkey string is encrypted, we need CURLOPT_KEYPASSWD.
// Usually openssl_export would make it encrypted, but the array from read usually has it raw or we can check.
// Let's assume it works without pass first, or retry.
// Actually, usually we can just provide the PFX directly to CURL if supported, but separate PEMs is safer x-platform.

curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // For testing locally/homolog
curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);

$response = curl_exec($ch);

if (curl_errno($ch)) {
    echo 'Curl Error: ' . curl_error($ch) . "\n";
} else {
    echo "Response received:\n";

    // Formatting XML for display
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

// Cleanup
unlink($certPemFile);
unlink($keyPemFile);
?>