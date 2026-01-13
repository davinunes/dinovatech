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
    if (empty($rootId)) {
        $uriRef = "";
    } else {
        $uriRef = "#" . $rootId;
    }

    if ($variation === 'uri_empty' || $variation === 'support_combo') {
        // If it was already empty (from buildGerarNfseXml), keeps empty.
        // If it had an ID (Consultar), forces empty and removes ID attribute.
        if (!empty($rootId)) {
            $uriRef = "";
            $rootXml = str_replace(' Id="' . $rootId . '"', '', $rootXml);
        }
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
    $numeroRps = rand(2000, 9999);
    $dataHoje = date('Y-m-d');

    // Content structure matching Support Example (URI="")
    // In RPS context: Rps -> InfDeclaracaoPrestacaoServico

    $cpfTomador = $input['cpf_tomador'] ?? '01691128104';

    // Dynamic Service Data
    $itemLista = $input['item_lista'] ?? '01.07';
    $codigoCnae = $input['codigo_cnae'] ?? '6204000';
    $codigoTributacao = $input['codigo_tributacao'] ?? '7';
    $discriminacao = $input['discriminacao'] ?? "Teste de Integracao via WebService - RPS $numeroRps";

    $valorServicos = $input['valor'] ?? '10.00';
    $issRetido = $input['iss_retido'] ?? '2'; // 1=Sim, 2=Não

    $infRps = <<<XML
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
                <ValorServicos>$valorServicos</ValorServicos>
                <ValorDeducoes>0.00</ValorDeducoes>
                <ValorPis>0.00</ValorPis>
                <ValorCofins>0.00</ValorCofins>
                <ValorInss>0.00</ValorInss>
                <ValorIr>0.00</ValorIr>
                <ValorCsll>0.00</ValorCsll>
                <OutrasRetencoes>0.00</OutrasRetencoes>
                <ValTotTributos>0.00</ValTotTributos>
                <ValorIss>0.00</ValorIss>
                <Aliquota>0.00</Aliquota>
                <DescontoIncondicionado>0.00</DescontoIncondicionado>
                <DescontoCondicionado>0.00</DescontoCondicionado>
            </Valores>
            <IssRetido>$issRetido</IssRetido>
            <ItemListaServico>$itemLista</ItemListaServico>
            <CodigoCnae>$codigoCnae</CodigoCnae>
            <CodigoTributacaoMunicipio>$codigoTributacao</CodigoTributacaoMunicipio>
            <CodigoNbs>115080000</CodigoNbs>
            <Discriminacao>$discriminacao</Discriminacao>
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
                    <Cpf>$cpfTomador</Cpf>
                </CpfCnpj>
            </IdentificacaoTomador>
            <RazaoSocial>Davi Nunes de França</RazaoSocial>
            <Endereco>
                <Endereco>QI 24 Lotes 1 a 13 (Residencial Miami Beach)</Endereco>
                <Numero>1</Numero>
                <Complemento>104E</Complemento>
                <Bairro>Setor Industrial (Taguatinga)</Bairro>
                <CodigoMunicipio>5300108</CodigoMunicipio>
                <Uf>DF</Uf>
                <Cep>72135902</Cep>
            </Endereco>
            <Contato>
                <Telefone>61996757676</Telefone>
                <Email>davi.nunes@gmail.com</Email>
            </Contato>
        </TomadorServico>
        <OptanteSimplesNacional>2</OptanteSimplesNacional>
        <IncentivoFiscal>2</IncentivoFiscal>
    </InfDeclaracaoPrestacaoServico>
XML;

    // Structure for Signature: Wrapper <Rps> contains <Inf...> and <Signature>
    $rootXml = "<Rps>$infRps</Rps>";

    // Use Empty ID to trigger URI="" in caller logic
    $rootId = "";

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
    // CRITICAL: If signing a specific ID, we must canonicalize ONLY the target node?
    // No, Reference URI="#ID" means the signature transforms will pick that element.
    // BUT, the DigestValue MUST be calculated over the Canonicalized Target Element.

    if ($uriRef !== "" && $uriRef[0] === '#') {
        // Signing specific ID
        $idToSign = substr($uriRef, 1);
        $xpath = new DOMXPath($dom);
        $node = $xpath->query("//*[@Id='$idToSign']")->item(0);
        if ($node) {
            $canonicalized = $node->C14N(false, false, null, null);
        }
    }

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

    if ($variation === 'no_cdata' || $variation === 'support_combo' || $variation === 'proven_protocol') {
        $payloadForEnvelope = htmlspecialchars($finalXmlPayload, ENT_XML1, 'UTF-8');
        $cabecForEnvelope = htmlspecialchars($nfseCabecMsg, ENT_XML1, 'UTF-8');

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
      <nfseCabecMsg><![CDATA[$nfseCabecMsg]]></nfseCabecMsg>
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