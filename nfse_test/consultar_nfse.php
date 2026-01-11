<?php

// Basic configuration
// Basic configuration
// URL 1: Homologação Dados Oficiais (Blocked by Cloudflare)
// $wsdl_homolog = 'https://www.issnetonline.com.br/apresentacao/df/webservicenfse204/nfse.asmx?wsdl';
// $endpoint_homolog = 'https://www.issnetonline.com.br/apresentacao/df/webservicenfse204/nfse.asmx';

// URL 2: Homologação Fictícia (Trying this to bypass WAF)
$endpoint_homolog = 'https://www.issnetonline.com.br/homologaabrasf/webservicenfse204/nfse.asmx';


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
    'SOAPAction: "http://www.abrasf.org.br/nfse.xsd/ConsultarNfseServicoPrestado"',
    'Content-Length: ' . strlen($soapEnvelope)
]);

// IMPORTANT: Set User-Agent to mimic a browser or valid client to bypass some WAFs
curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36');

// Client Certificate Settings
curl_setopt($ch, CURLOPT_SSLCERT, $certPemFile);
curl_setopt($ch, CURLOPT_SSLKEY, $keyPemFile);
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