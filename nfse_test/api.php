<?php
header('Content-Type: application/json');

$input = json_decode(file_get_contents('php://input'), true);
if (!$input) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid JSON']);
    exit;
}

$action = $input['action'] ?? 'direct_a1';
$method = $input['method'] ?? 'consultar'; // 'consultar' or 'gerar'
$variation = $input['variation'] ?? 'support_combo'; // Default to what support uses

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

    // Build XML based on Method
    if ($method === 'gerar') {
        $xmlComponents = buildGerarNfseXml($input);
    } else {
        $xmlComponents = buildConsultarXml($input);
    }

    $rootXml = $xmlComponents['root'];
    $rootId = $xmlComponents['id'];

    // VARIATION LOGIC
    $uriRef = "#" . $rootId;
    if ($variation === 'uri_empty' || $variation === 'support_combo') {
        $uriRef = "";
        $rootXml = str_replace(' Id="' . $rootId . '"', '', $rootXml);
    }

    // Sign
    $signedXml = assinarRoot($rootXml, $certs, $uriRef, $variation);

    // Send
    sendSoap($signedXml, $endpoint_url, $certs, $variation, $method);
    exit;
}

// ... (Rest of actions skipped)

// --- HELPERS ---

function buildConsultarXml($input)
{
    $cnpj = $input['cnpj'] ?? '';
    // ... [Same mapping as before] ...
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

function buildGerarNfseXml($input)
{
    $cnpjPrestador = $input['cnpj'] ?? '61733714000101';
    $imPrestador = $input['im'] ?? '0841147200111';
    $numeroRps = rand(2000, 9999); // Random RPS to avoid duplication error
    $dataHoje = date('Y-m-d');

    // TOMADOR GENERICO (Test)
    // Using a valid sample CPF or the User's CPF if available, otherwise just use the Prestador's own CNPJ as Tomador (Self-invoice for test)
    $cpfTomador = $input['cpf_tomador'] ?? '00000000191'; // Banco do Brasil test or similar
    // Actually, let's use a dummy CPF unless provided

    // Content structure matching Support Example
    $rpsContent = <<<XML
<Rps>
    <InfDeclaracaoPrestacaoServico>
        <Rps>
            <IdentificacaoRps>
                <Numero>$numeroRps</Numero>
                <Serie>A</Serie>
                <Tipo>1</Tipo>
            </IdentificacaoRps>
            <DataEmissao>$dataHoje</DataEmissao>
            <Status>1</Status>
        </Rps>
        <Competencia>$dataHoje</Competencia>
        <Servico>
            <Valores>
                <ValorServicos>1.00</ValorServicos>
                <ValorDeducoes>0</ValorDeducoes>
                <ValorPis>0</ValorPis>
                <ValorCofins>0</ValorCofins>
                <ValorInss>0</ValorInss>
                <ValorIr>0</ValorIr>
                <ValorCsll>0</ValorCsll>
                <OutrasRetencoes>0</OutrasRetencoes>
                <ValTotTributos>0</ValTotTributos>
                <ValorIss>0.05</ValorIss>
                <Aliquota>5.00</Aliquota>
                <DescontoIncondicionado>0</DescontoIncondicionado>
                <DescontoCondicionado>0</DescontoCondicionado>
            </Valores>
            <IssRetido>2</IssRetido>
            <ItemListaServico>01.07</ItemListaServico>
            <CodigoCnae>6203100</CodigoCnae>
            <CodigoTributacaoMunicipio>620310000</CodigoTributacaoMunicipio>
            <Discriminacao>Teste de Integracao via WebService - RPS $numeroRps</Discriminacao>
            <CodigoMunicipio>5300108</CodigoMunicipio>
            <ExigibilidadeISS>1</ExigibilidadeISS>
            <MunicipioIncidencia>5300108</MunicipioIncidencia>
        </Servico>
        <Prestador>
            <CpfCnpj>
                <Cnpj>$cnpjPrestador</Cnpj>
            </CpfCnpj>
            <InscricaoMunicipal>$imPrestador</InscricaoMunicipal>
        </Prestador>
        <TomadorServico>
            <IdentificacaoTomador>
                <CpfCnpj>
                    <Cpf>01691128104</Cpf>
                </CpfCnpj>
            </IdentificacaoTomador>
            <RazaoSocial>TOMADOR DE TESTE</RazaoSocial>
        </TomadorServico>
        <OptanteSimplesNacional>2</OptanteSimplesNacional>
        <IncentivoFiscal>2</IncentivoFiscal>
    </InfDeclaracaoPrestacaoServico>
</Rps>
XML;

    $rootId = "GerarNfseEnvio"; // Not standard ID usage in support example, but they used root signing
    // Actually the support example signed the Rps element inside?
    // Let's look at the file: <GerarNfseEnvio> <Rps> ... <Signature> ... </Rps> </GerarNfseEnvio>
    // Wait, the Signature is INSIDE <Rps> ? 
    // Checking file line 261: <Rps> ... <Signature> ... </Rps>
    // YES. The signature is inside Rps.
    // So we are signing the RPS content, not the Envelope.
    // And InfDeclaracaoPrestacaoServico is what is likely signed or the whole Rps.
    // But usually Rps contains InfDeclaracaoPrestacaoServico AND Signature.

    // Correction: We need to sign InfDeclaracaoPrestacaoServico? Or Rps? 
    // If Reference URI="", it signs the parent (Rps).
    // So structure is: <GerarNfseEnvio> <Rps> [Content] [Signature] </Rps> </GerarNfseEnvio>

    // IMPORTANT: The Support Example `GerarNfseEnvio` has `xmlns` on the root.
    // The previous implementation signed the ROOT of the payload.
    // Here, we have `GerarNfseEnvio` -> `Rps`

    // I will construct the `Rps` content, sign it (wrap in Rps), and then wrap in GerarNfseEnvio.
    // Actually, `assinarRoot` appends signature to the end.
    // So if I pass `<Rps>...</Rps>`, it will become `<Rps>...<Signature>...</Signature></Rps>`.
    // Then I wrap that in `GerarNfseEnvio`.

    $rootXml = $rpsContent;
    $rootId = "Rps"; // Conceptual ID, though we use URI=""

    // Wait, `assinarRoot` expects a full XML string. 
    // If I pass `<Rps>...`, it returns signed `<Rps>...`.
    // Then I just need to Wrap it.

    return ['root' => $rootXml, 'id' => $rootId, 'wrapper' => 'GerarNfseEnvio'];
}

function assinarRoot($xmlString, $certs, $uriRef, $variation)
{
    // ... [Same Signature Logic] ...
    // Note: If we need to sign a specific inner ID, we would need to pass that.
    // But with URI="", it signs the root of what is passed.

    $dom = new DOMDocument('1.0', 'UTF-8');
    $dom->loadXML($xmlString);

    $canonicalized = $dom->C14N(false, false, null, null);
    $digestValue = base64_encode(sha1($canonicalized, true));

    // Namespace Prefix Logic
    $ns = 'ds'; // Default
    $nsDecl = ' xmlns:ds="http://www.w3.org/2000/09/xmldsig#"';

    if ($variation === 'no_prefix' || $variation === 'support_combo') {
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

    $finalXml = $dom->saveXML();
    $search1 = '<' . '?xml version="1.0" encoding="UTF-8"?' . '>';
    $search2 = '<' . '?xml version="1.0"?' . '>';
    $finalXml = str_replace($search1, '', $finalXml);
    $finalXml = str_replace($search2, '', $finalXml);
    return trim($finalXml);
}

function sendSoap($finalXmlPayload, $endpoint_url, $certsA1 = [], $variation = 'standard', $method = 'consultar')
{

    // If GerarNfse, we need to Wrap the Signed RPS in GerarNfseEnvio
    if ($method === 'gerar') {
        $finalXmlPayload = '<GerarNfseEnvio xmlns="http://www.abrasf.org.br/nfse.xsd">' . $finalXmlPayload . '</GerarNfseEnvio>';
    }

    $xmlDecl = '<' . '?xml version="1.0" encoding="UTF-8"?' . '>';
    $soapAction = ($method === 'gerar')
        ? 'http://nfse.abrasf.org.br/GerarNfse'
        : 'http://nfse.abrasf.org.br/ConsultarNfseServicoPrestado';

    $methodTag = ($method === 'gerar') ? 'GerarNfse' : 'ConsultarNfseServicoPrestado';

    // Strategy for Envelope CDATA vs Entities
    $nfseCabecMsg = "<cabecalho versao=\"2.04\" xmlns=\"http://www.abrasf.org.br/nfse.xsd\"><versaoDados>2.04</versaoDados></cabecalho>";

    if ($variation === 'no_cdata') {
        $payloadForEnvelope = htmlspecialchars($finalXmlPayload, ENT_XML1, 'UTF-8');
        $cabecForEnvelope = htmlspecialchars($xmlDecl . $nfseCabecMsg, ENT_XML1, 'UTF-8');

        $soapEnvelope = <<<XML
<soap:Envelope xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xmlns:xsd="http://www.w3.org/2001/XMLSchema" xmlns:soap="http://schemas.xmlsoap.org/soap/envelope/">
  <soap:Body>
    <$methodTag xmlns="http://nfse.abrasf.org.br">
      <nfseCabecMsg>$cabecForEnvelope</nfseCabecMsg>
      <nfseDadosMsg>$payloadForEnvelope</nfseDadosMsg>
    </$methodTag>
  </soap:Body>
</soap:Envelope>
XML;
    } else {
        // Standard CDATA
        $soapEnvelope = <<<XML
<soap:Envelope xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xmlns:xsd="http://www.w3.org/2001/XMLSchema" xmlns:soap="http://schemas.xmlsoap.org/soap/envelope/">
  <soap:Body>
    <$methodTag xmlns="http://nfse.abrasf.org.br">
      <nfseCabecMsg><![CDATA[$xmlDecl$nfseCabecMsg]]></nfseCabecMsg>
      <nfseDadosMsg><![CDATA[$finalXmlPayload]]></nfseDadosMsg>
    </$methodTag>
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
        'SOAPAction: "' . $soapAction . '"',
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