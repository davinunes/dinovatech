<?php
# O Git não detectou alteração
header('Content-Type: application/json');
// Manual UTC-3 calculation to ignore environment TZ issues
$dataHoje = gmdate('Y-m-d', time() - (3 * 3600));
date_default_timezone_set('UTC'); // Reset base

// Execution ONLY if called directly
if (basename(__FILE__) == basename($_SERVER['SCRIPT_FILENAME'])) {
    header('Content-Type: application/json');
    $input = json_decode(file_get_contents('php://input'), true);
    if (!$input) {
        echo json_encode(['status' => 'error', 'message' => 'Invalid JSON']);
        exit;
    }


    $action = $input['action'] ?? 'direct_a1';
    $method = $input['method'] ?? 'consultar'; // 'consultar' or 'gerar'
    $variation = $input['variation'] ?? 'support_combo'; // Default to what support uses

    // FORCE VARIATION based on known working configurations per method.
// Ignores input variation to prevent regression.

    $protocolMap = [
        'gerar' => 'support_combo',                  // Signed RPS Wrapper: Needs URI=""
        'consultar' => 'support_combo',              // Consultar Notas (Servico Prestado): Needs URI=""
        'consultar_rps' => 'support_combo',          // Consultar RPS: Needs URI=""
        'consultar_rps_disponivel' => 'support_combo', // Disponibilidade: Needs URI=""
        'consultar_url' => 'support_combo',          // Consultar URL: Model has URI=""
        'consultar_cadastral' => 'proven_protocol'   // Cadastral: Needs URI="#" (Legacy behavior)
    ];

    if (isset($protocolMap[$method])) {
        $variation = $protocolMap[$method];
    } else {
        // Fallback or default
        if (empty($variation))
            $variation = 'support_combo';
    }

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
        // Build XML based on Method
        if ($method === 'gerar') {
            $xmlComponents = buildGerarNfseXml($input);
        } elseif ($method === 'consultar_cadastral') {
            $xmlComponents = buildConsultarCadastralXml($input);
        } elseif ($method === 'consultar_rps') {
            $xmlComponents = buildConsultarNfseRpsXml($input);
        } elseif ($method === 'consultar_rps_disponivel') {
            $xmlComponents = buildConsultarRpsDisponivelXml($input);
        } elseif ($method === 'consultar_url') {
            $xmlComponents = buildConsultarUrlNfseXml($input);
        } else {
            $xmlComponents = buildConsultarXml($input);
        }

        $rootXml = $xmlComponents['root'];
        $rootId = $xmlComponents['id'];

        // VARIATION LOGIC
        $uriRef = "#" . $rootId;

        // DEBUG: Enforce protocol to match 2aa36ab legacy success
        // $variation = 'support_combo'; // REMOVED GLOBAL FORCE to respect JS input (proven_protocol)

        if ($variation === 'uri_empty' || $variation === 'support_combo') {
            // Legacy Logic Restoration (Commit 2aa36ab)
            // For this variation, we MUST strip the ID from the root element and set URI to empty.
            // This applies to ALL methods (Gerar, Consultar, ConsultarRPS).
            $uriRef = "";

            // Ensure replacement works even if $rootId is empty (though it shouldn't be for valid requests)
            if (!empty($rootId)) {
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

}

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

function buildConsultarCadastralXml($input)
{
    $cnpj = $input['cnpj'] ?? '';
    $im = $input['im'] ?? '';
    $cpf = $input['cpf'] ?? '';

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
    $pedidoContent .= "</Pedido>";

    // No specific inner ID usually for this, so we sign the root.
    // However, we need to verify if the root tag needs an ID.
    // Based on standard, it doesn't always, but our assinarRoot handles empty URI.
    $rootXml = '<ConsultarDadosCadastraisEnvio xmlns="http://www.abrasf.org.br/nfse.xsd">' . $pedidoContent . '</ConsultarDadosCadastraisEnvio>';

    return ['root' => $rootXml, 'id' => ''];
}

function buildConsultarNfseRpsXml($input)
{
    $cnpj = $input['cnpj'] ?? '';
    $im = $input['im'] ?? '';
    $numeroRps = $input['numero_rps'] ?? '';
    $serieRps = $input['serie_rps'] ?? '8';
    $tipoRps = $input['tipo_rps'] ?? '1';

    $pedidoContent = "<Pedido>";
    $pedidoContent .= "<IdentificacaoRps>";
    $pedidoContent .= "<Numero>$numeroRps</Numero>";
    $pedidoContent .= "<Serie>$serieRps</Serie>";
    $pedidoContent .= "<Tipo>$tipoRps</Tipo>";
    $pedidoContent .= "</IdentificacaoRps>";
    $pedidoContent .= "<Prestador>";
    $pedidoContent .= "<CpfCnpj><Cnpj>$cnpj</Cnpj></CpfCnpj>";
    $pedidoContent .= "<InscricaoMunicipal>$im</InscricaoMunicipal>";
    $pedidoContent .= "</Prestador>";
    $pedidoContent .= "</Pedido>";

    $rootId = "ConsultarNfseRpsEnvio";
    $rootXml = '<ConsultarNfseRpsEnvio xmlns="http://www.abrasf.org.br/nfse.xsd" Id="' . $rootId . '">' . $pedidoContent . '</ConsultarNfseRpsEnvio>';

    return ['root' => $rootXml, 'id' => $rootId];
}

function buildConsultarRpsDisponivelXml($input)
{
    $cnpj = $input['cnpj'] ?? '';
    $im = $input['im'] ?? '';
    $serieRps = $input['serie_rps'] ?? '8';
    $tipoRps = $input['tipo_rps'] ?? '1';
    $numeroRps = $input['numero_rps'] ?? '';

    $pedidoContent = "<Pedido>";
    $pedidoContent .= "<Prestador>";
    $pedidoContent .= "<CpfCnpj><Cnpj>$cnpj</Cnpj></CpfCnpj>";
    $pedidoContent .= "<InscricaoMunicipal>$im</InscricaoMunicipal>";
    $pedidoContent .= "</Prestador>";

    if (!empty($numeroRps)) {
        $pedidoContent .= "<IdentificacaoRps>";
        $pedidoContent .= "<Numero>$numeroRps</Numero>";
        $pedidoContent .= "<Serie>$serieRps</Serie>";
        $pedidoContent .= "<Tipo>$tipoRps</Tipo>";
        $pedidoContent .= "</IdentificacaoRps>";
    }

    $pedidoContent .= "<Pagina>1</Pagina>";

    $pedidoContent .= "</Pedido>";

    $rootId = "ConsultarRpsDisponivelEnvio";
    $rootXml = '<ConsultarRpsDisponivelEnvio xmlns="http://www.abrasf.org.br/nfse.xsd" Id="' . $rootId . '">' . $pedidoContent . '</ConsultarRpsDisponivelEnvio>';

    return ['root' => $rootXml, 'id' => $rootId];
}


function buildGerarNfseXml($input)
{
    $cnpjPrestador = $input['cnpj'] ?? '61733714000101';
    $imPrestador = $input['im'] ?? '0841147200111';
    $iePrestador = $input['ie'] ?? '';
    $iePrestadorTag = !empty($iePrestador) ? "<InscricaoEstadual>$iePrestador</InscricaoEstadual>" : "";
    $numeroRps = $input['numero_rps'] ?? '';
    $serieRps = $input['serie_rps'] ?? '8';
    $tipoRps = $input['tipo_rps'] ?? '1';

    if (empty($numeroRps)) {
        $numeroRps = rand(2000, 9999);
    }
    // Manual UTC-3 Calculation because Environment Timezone is unreliable
    $dataHoje = gmdate('Y-m-d', time() - (3 * 3600));

    // Content structure matching Support Example (URI="")
    // In RPS context: Rps -> InfDeclaracaoPrestacaoServico

    $cpfTomador = $input['cpf_tomador'] ?? '01691128104';

    // Dynamic Service Data
    $itemLista = $input['item_lista'] ?? '01.07';
    $codigoCnae = $input['codigo_cnae'] ?? '6204000';
    $codigoTributacao = $input['codigo_tributacao'] ?? '7';
    // NBS default to what user mentioned
    $codigoNbs = $input['codigo_nbs'] ?? '';
    $aliquota = $input['aliquota'] ?? '0';

    // Helper to sanitize strings (Remove accents, special chars, keep newlines as literal \s\n)
    $cleanString = function ($str) {
        if (empty($str))
            return "";

        // 2. Manual Transliteration (Safer than iconv)
        $map = [
            'á' => 'a',
            'à' => 'a',
            'ã' => 'a',
            'â' => 'a',
            'ä' => 'a',
            'é' => 'e',
            'è' => 'e',
            'ê' => 'e',
            'ë' => 'e',
            'í' => 'i',
            'ì' => 'i',
            'î' => 'i',
            'ï' => 'i',
            'ó' => 'o',
            'ò' => 'o',
            'õ' => 'o',
            'ô' => 'o',
            'ö' => 'o',
            'ú' => 'u',
            'ù' => 'u',
            'û' => 'u',
            'ü' => 'u',
            'ç' => 'c',
            'ñ' => 'n',
            'Á' => 'A',
            'À' => 'A',
            'Ã' => 'A',
            'Â' => 'A',
            'Ä' => 'A',
            'É' => 'E',
            'È' => 'E',
            'Ê' => 'E',
            'Ë' => 'E',
            'Í' => 'I',
            'Ì' => 'I',
            'Î' => 'I',
            'Ï' => 'I',
            'Ó' => 'O',
            'Ò' => 'O',
            'Õ' => 'O',
            'Ô' => 'O',
            'Ö' => 'O',
            'Ú' => 'U',
            'Ù' => 'U',
            'Û' => 'U',
            'Ü' => 'U',
            'Ç' => 'C',
            'Ñ' => 'N'
        ];
        $str = strtr($str, $map);

        // 3. Whitelist: Alphanumerics, space, hyphen, backslash, brackets, punctuation
        return preg_replace('/[^a-zA-Z0-9 \-\\\\\[\]\n\r\.,;]/', ' ', $str);
    };

    // Sanitize Discriminacao
    $discriminacaoRaw = $input['discriminacao'] ?? "Teste de Integracao via WebService - RPS $numeroRps";

    // Apply \s\n conversion SPECIFICALLY for Discriminacao (as confirmed requirement)
    // We do this BEFORE cleanString so cleanString processes the backslashes correctly (allowed)
    // Actually, cleanString allows \n. But we want literal \s\n.
    // So:
    $discriminacaoPre = str_replace(["\r\n", "\r", "\n"], '\s\n', $discriminacaoRaw);
    $discriminacao = $cleanString($discriminacaoPre);

    $valorServicos = $input['valor'] ?? '10.00';
    $valorServicos = number_format((float) $valorServicos, 2, '.', ''); // Ensure 2 decimals
    $issRetido = $input['iss_retido'] ?? '2'; // 1=Sim, 2=Não

    // Auto-calculate ISS Value to prevent E232
    $valorIss = '0.00';
    if ((float) $aliquota > 0) {
        $valorIss = number_format((float) $valorServicos * ((float) $aliquota / 100), 2, '.', '');
    }

    // Configurable Optante Simples
    $optanteSimples = $input['optante_simples'] ?? '2';

    // We need a unique ID for InfDeclaracaoPrestacaoServico regardless of RPS
    // We need a unique ID for InfDeclaracaoPrestacaoServico regardless of RPS
    $rpsId = "rps" . ($numeroRps ?: rand(10000, 99999));

    // Prestador Info (Add Address if needed for signature, but usually XML only asks for Cnpj/Inscrica)
    // Actually, Prestador tag in Rps usually only has Cnpj/Inscricao.
    // Address is in ConfiguracoesEmissor if needed elsewhere.

    $razaoSocialTomador = $cleanString($input['tomador']['razao_social'] ?? '');
    $cpfCnpjTomador = $cleanString($input['tomador']['cpf_cnpj'] ?? ''); // Plain numbers
    $enderecoTomador = $cleanString($input['tomador']['endereco'] ?? '');
    $numeroTomador = $cleanString($input['tomador']['numero'] ?? '');
    $complementoTomador = $cleanString($input['tomador']['complemento'] ?? '');
    $bairroTomador = $cleanString($input['tomador']['bairro'] ?? '');
    $cepTomador = $cleanString($input['tomador']['cep'] ?? '');
    $ufTomador = $cleanString($input['tomador']['uf'] ?? '');
    $cidadeTomador = $cleanString($input['tomador']['codigo_municipio'] ?? '5300108'); // IBGE
    $telefoneTomador = $cleanString($input['tomador']['telefone'] ?? '');
    $emailTomador = $cleanString($input['tomador']['email'] ?? '');
    $imTomador = $cleanString($input['tomador']['im'] ?? ''); // IM of the Client

    // Decide if CPF or CNPJ
    $tomadorCpfCnpjTag = "";
    if (strlen($cpfCnpjTomador) == 11) {
        $tomadorCpfCnpjTag = "<Cpf>$cpfCnpjTomador</Cpf>";
    } else {
        $tomadorCpfCnpjTag = "<Cnpj>$cpfCnpjTomador</Cnpj>";
    }

    // Prepare Contato Block (Choice: Telefone+Email OR Email)
    // FIX: Email sanitization allowed only alphanumeric, breaking 'gmail.com'. 
    // FIX: Telefone cannot be empty tag.

    $emailTomadorRaw = $input['tomador']['email'] ?? '';
    // Allow alphanumeric, @, dot, underscore, hyphen
    $emailTomadorClean = preg_replace('/[^a-zA-Z0-9@._-]/', '', $emailTomadorRaw);

    $contatoTag = "";
    if (!empty($telefoneTomador) && !empty($emailTomadorClean)) {
        $contatoTag = "<Contato><Telefone>$telefoneTomador</Telefone><Email>$emailTomadorClean</Email></Contato>";
    } elseif (!empty($emailTomadorClean)) {
        $contatoTag = "<Contato><Email>$emailTomadorClean</Email></Contato>";
    } elseif (!empty($telefoneTomador)) {
        // Technically Schema says: Sequence(Telefone, Email [opt]) OR Sequence(Email). 
        // If only Telefone, we must use first sequence.
        $contatoTag = "<Contato><Telefone>$telefoneTomador</Telefone></Contato>";
    }

    $infRps = "<InfDeclaracaoPrestacaoServico Id=\"$rpsId\">";

    // Check for "Avulso" generation (No RPS block)
    if ($serieRps && (strtoupper($serieRps) === 'AVULSO' || strtoupper($serieRps) === 'NONE')) {
        // Skip RPS block to generate "Nota Avulsa"
    } else {
        // Standard generation with RPS
        $infRps .= <<<XML
        <Rps>
            <IdentificacaoRps>
                <Numero>$numeroRps</Numero>
                <Serie>$serieRps</Serie>
                <Tipo>$tipoRps</Tipo>
            </IdentificacaoRps>
            <DataEmissao>$dataHoje</DataEmissao>
            <Status>1</Status>
        </Rps>
XML;
    }

    // Pre-calc IM tag
    $imTomadorTag = !empty($imTomador) ? "<InscricaoMunicipal>$imTomador</InscricaoMunicipal>" : "";

    $infRps .= <<<XML
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
                <ValorIss>$valorIss</ValorIss>
                <Aliquota>$aliquota</Aliquota>
                <DescontoIncondicionado>0.00</DescontoIncondicionado>
                <DescontoCondicionado>0.00</DescontoCondicionado>
            </Valores>
            <IssRetido>$issRetido</IssRetido>
            <ItemListaServico>$itemLista</ItemListaServico>
            <CodigoCnae>$codigoCnae</CodigoCnae>
            <CodigoTributacaoMunicipio>$codigoTributacao</CodigoTributacaoMunicipio>
XML;

    // Use default NBS from Success XML if empty (115080000)
    $codigoNbs = $input['codigo_nbs'] ?? '115080000';
    if (!empty($codigoNbs)) {
        $infRps .= "<CodigoNbs>$codigoNbs</CodigoNbs>";
    }

    // Build Dynamic Address Block
    $enderecoBlock = "";
    if (!empty($enderecoTomador))
        $enderecoBlock .= "                <Endereco>$enderecoTomador</Endereco>\n";
    if (!empty($numeroTomador))
        $enderecoBlock .= "                <Numero>$numeroTomador</Numero>\n";
    if (!empty($complementoTomador))
        $enderecoBlock .= "                <Complemento>$complementoTomador</Complemento>\n";
    if (!empty($bairroTomador))
        $enderecoBlock .= "                <Bairro>$bairroTomador</Bairro>\n";
    if (!empty($cidadeTomador))
        $enderecoBlock .= "                <CodigoMunicipio>$cidadeTomador</CodigoMunicipio>\n";
    if (!empty($ufTomador))
        $enderecoBlock .= "                <Uf>$ufTomador</Uf>\n";
    if (!empty($cepTomador))
        $enderecoBlock .= "                <Cep>$cepTomador</Cep>\n";
    // If block is empty, ensure at least empty Address tag (or let Schema fail if strict)
    // Actually, schema usually requires at least one address field or the tag itself might be optional?
    // Usually <Endereco> tag is mandatory for <Tomador>. If all children empty, we might send empty wrapper.
    // <Endereco></Endereco> is valid if children are optional.

    // Outras Informações (Legal Text) - Merged into Discriminacao above
    // Code removed to resolve Schema Error

    $outrasInformacoesTag = ""; // Force empty

    $infRps .= <<<XML
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
            $iePrestadorTag
        </Prestador>
        <TomadorServico>
            <IdentificacaoTomador>
                <CpfCnpj>
                    $tomadorCpfCnpjTag
                </CpfCnpj>
                $imTomadorTag
            </IdentificacaoTomador>
            <RazaoSocial>$razaoSocialTomador</RazaoSocial>
            <Endereco>
$enderecoBlock
            </Endereco>
            $contatoTag
        </TomadorServico>
        <OptanteSimplesNacional>$optanteSimples</OptanteSimplesNacional>
        <IncentivoFiscal>2</IncentivoFiscal>
        $outrasInformacoesTag
    </InfDeclaracaoPrestacaoServico>
XML;

    // We pass ID here. The caller logic (api.php top level) will decide to use it or not based on variation.
    // for PROVEN_PROTOCOL, we need it.

    // Structure for Signature: Wrapper <Rps> contains <Inf...> and <Signature>
    $rootXml = "<Rps>$infRps</Rps>";

    return ['root' => $rootXml, 'id' => $rpsId, 'wrapper' => 'GerarNfseEnvio'];
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

    if ($variation === 'no_prefix' || $variation === 'support_combo' || $variation === 'proven_protocol') {
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



function sendSoap($finalXmlPayload, $endpoint_url, $certsA1 = [], $variation = 'standard', $method = 'consultar', $returnResponse = false)
{

    // If GerarNfse, we need to Wrap the Signed RPS in GerarNfseEnvio
    if ($method === 'gerar') {
        $finalXmlPayload = '<GerarNfseEnvio xmlns="http://www.abrasf.org.br/nfse.xsd">' . $finalXmlPayload . '</GerarNfseEnvio>';
    }

    $xmlDecl = '<' . '?xml version="1.0" encoding="UTF-8"?' . '>';
    $soapAction = '';
    $methodTag = '';


    if ($method === 'gerar') {
        $soapAction = 'http://nfse.abrasf.org.br/GerarNfse';
        $methodTag = 'GerarNfse';
    } elseif ($method === 'consultar_cadastral') {
        $soapAction = 'http://nfse.abrasf.org.br/ConsultarDadosCadastrais';
        $methodTag = 'ConsultarDadosCadastrais';
    } elseif ($method === 'consultar_rps') {
        $soapAction = 'http://nfse.abrasf.org.br/ConsultarNfsePorRps';
        $methodTag = 'ConsultarNfsePorRps';
    } elseif ($method === 'consultar_rps_disponivel') {
        $soapAction = 'http://nfse.abrasf.org.br/ConsultarRpsDisponivel';
        $methodTag = 'ConsultarRpsDisponivel';
    } elseif ($method === 'consultar_url') {
        $soapAction = 'http://nfse.abrasf.org.br/ConsultarUrlNfse';
        $methodTag = 'ConsultarUrlNfse';
    } else {
        $soapAction = 'http://nfse.abrasf.org.br/ConsultarNfseServicoPrestado';
        $methodTag = 'ConsultarNfseServicoPrestado';
    }

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

    $result = [
        'status' => $httpCode == 200 ? 'success' : 'fail',
        'http_code' => $httpCode,
        'endpoint' => $endpoint_url,
        'headers' => $responseHeaders,
        'response_body' => $responseBody,
        'request_envelope' => $soapEnvelope,
        'curl_error' => $curlError
    ];

    if ($returnResponse) {
        return $result;
    }

    echo json_encode($result);
}



function buildConsultarUrlNfseXml($input)
{
    $cnpj = $input['cnpj'] ?? '';
    $im = $input['im'] ?? '';
    $numero = $input['numero_nota'] ?? $input['numero'] ?? '';
    $numeroRps = $input['numero_rps'] ?? '';
    $serieRps = $input['serie_rps'] ?? '8';
    $tipoRps = $input['tipo_rps'] ?? '1';

    $pedidoContent = "<Pedido>";
    $pedidoContent .= "<Prestador>";
    $pedidoContent .= "<CpfCnpj><Cnpj>$cnpj</Cnpj></CpfCnpj>";
    $pedidoContent .= "<InscricaoMunicipal>$im</InscricaoMunicipal>";
    $pedidoContent .= "</Prestador>";

    // Flexible Logic: Prioritize Note Number, Fallback to RPS
    if (!empty($numero) && $numero != '0') {
        $pedidoContent .= "<NumeroNfse>$numero</NumeroNfse>";
    } elseif (!empty($numeroRps)) {
        $pedidoContent .= "<IdentificacaoRps>";
        $pedidoContent .= "<Numero>$numeroRps</Numero>";
        $pedidoContent .= "<Serie>$serieRps</Serie>";
        $pedidoContent .= "<Tipo>$tipoRps</Tipo>";
        $pedidoContent .= "</IdentificacaoRps>";
    }

    $pedidoContent .= "<Pagina>1</Pagina>";
    $pedidoContent .= "</Pedido>";

    $rootId = "ConsultarUrlNfseEnvio";

    $rootXml = '<ConsultarUrlNfseEnvio xmlns="http://www.abrasf.org.br/nfse.xsd" Id="' . $rootId . '">' . $pedidoContent . '</ConsultarUrlNfseEnvio>';

    return ['root' => $rootXml, 'id' => $rootId];
}
?>