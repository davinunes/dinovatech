<?php
// dinovatech/nfse_nacional_test/api.php - Backend de Testes Interativo para NFS-e Padrão Nacional
header('Content-Type: application/json; charset=utf-8');
date_default_timezone_set('America/Sao_Paulo');

require_once __DIR__ . '/../../database.php';
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../helpers/EncryptionHelper.php';
require_once __DIR__ . '/../modules/Fiscal/bootstrap.php';

use Dinovatech\Modules\Fiscal\DTOs\NfseData;
use Dinovatech\Modules\Fiscal\Formatters\DpsIdGenerator;
use Dinovatech\Modules\Fiscal\Security\CertificateManager;
use Dinovatech\Modules\Fiscal\Security\XmlSigner;
use Dinovatech\Modules\Fiscal\Builders\DpsXmlBuilder;
use Dinovatech\Modules\Fiscal\Parsers\NacionalResponseParser;

$response = [
    'success' => false,
    'message' => 'Ação não especificada.',
    'details' => '',
    'dps_id' => '',
    'signed_xml' => '',
    'envelope_soap' => '',
    'response_xml' => '',
    'http_code' => 0,
    'erros' => []
];

try {
    $link = DBConnect();
    if (!$link) {
        throw new Exception("Falha na conexão com o banco de dados.");
    }

    $qConfig = "SELECT * FROM ConfiguracoesEmissor LIMIT 1";
    $resConfig = DBExecute($link, $qConfig);
    $configEmissor = $resConfig ? mysqli_fetch_assoc($resConfig) : null;
    DBClose($link);

    if (!$configEmissor) {
        throw new Exception("Configurações do emissor não encontradas no banco de dados.");
    }

    // Carrega o conteúdo do Certificado A1
    $pfxContent = null;
    if (!empty($configEmissor['certificado_pfx_base64'])) {
        $pfxContent = $configEmissor['certificado_pfx_base64'];
    } elseif (!empty($configEmissor['caminho_certificado']) && file_exists($configEmissor['caminho_certificado'])) {
        $pfxContent = file_get_contents($configEmissor['caminho_certificado']);
    }

    if (!$pfxContent) {
        throw new Exception("Certificado digital A1 não configurado nas Configurações Fiscais.");
    }

    // Inicializa CertificateManager
    $certManager = new CertificateManager($pfxContent, $configEmissor['senha_certificado'] ?? '');
    $signer = new XmlSigner($certManager);
    $parser = new NacionalResponseParser();

    $action = $_POST['action'] ?? $_GET['action'] ?? 'preview';

    if ($action === 'vincular_fatura') {
        $idFatura = (int)($_POST['id_fatura'] ?? 0);
        $numNota = trim($_POST['numero_nota'] ?? '54');
        $numDps = trim($_POST['numero_dps'] ?? '1');
        $serieDps = trim($_POST['serie_dps'] ?? '15');
        $chaveNfse = trim($_POST['chave_nfse'] ?? 'NFS53001081261733714000101000000000005426091788568900');
        $xmlEnvio = $_POST['xml_envio'] ?? '';
        $xmlRetorno = $_POST['xml_retorno'] ?? '';

        if (!$idFatura) {
            throw new Exception("Informe o ID da Fatura para vincular a NFS-e.");
        }

        $link = DBConnect();
        $idFaturaEsc = mysqli_real_escape_string($link, $idFatura);

        // Checa se a fatura existe
        $resCheckFat = DBExecute($link, "SELECT id_fatura FROM Faturas WHERE id_fatura = '$idFaturaEsc'");
        if (!$resCheckFat || mysqli_num_rows($resCheckFat) === 0) {
            DBClose($link);
            throw new Exception("A Fatura #{$idFatura} NÃO existe no banco de dados.");
        }

        $numNotaEsc = mysqli_real_escape_string($link, $numNota);
        $numDpsEsc = mysqli_real_escape_string($link, $numDps);
        $serieDpsEsc = mysqli_real_escape_string($link, $serieDps);
        $chaveEsc = mysqli_real_escape_string($link, $chaveNfse);
        $cleanChave = preg_replace('/^NFS/i', '', trim($chaveNfse));
        $urlPdfEsc = mysqli_real_escape_string($link, (!empty($cleanChave) && strlen($cleanChave) >= 40)
            ? "https://www.nfse.gov.br/EmissorNacional/Notas/Visualizar/Index/{$cleanChave}"
            : "https://nfse.fazenda.df.gov.br/NfseTax/");
        $xmlEnvioEsc = mysqli_real_escape_string($link, $xmlEnvio);
        $xmlRetornoEsc = mysqli_real_escape_string($link, $xmlRetorno);

        // Checa colunas extras do Padrão Nacional
        $chkProvCol = DBExecute($link, "SHOW COLUMNS FROM NfseEmissoes LIKE 'provider'");
        $hasExtraCols = ($chkProvCol && mysqli_num_rows($chkProvCol) > 0);
        $extraUpdate = $hasExtraCols ? ", provider = 'nacional', chave_nfse = '$chaveEsc', url_visualizacao_nacional = '$urlPdfEsc'" : "";

        // Preserva a id_emissao existente se houver, atualizando os dados in-place
        $resExist = DBExecute($link, "SELECT id_emissao FROM NfseEmissoes WHERE id_fatura = '$idFaturaEsc' ORDER BY id_emissao DESC LIMIT 1");
        if ($resExist && mysqli_num_rows($resExist) > 0) {
            $rowExist = mysqli_fetch_assoc($resExist);
            $idEmissao = $rowExist['id_emissao'];
            $qSave = "UPDATE NfseEmissoes SET
                numero_rps = '$numDpsEsc', serie_rps = '$serieDpsEsc', numero_nota = '$numNotaEsc',
                codigo_verificacao = '$chaveEsc', ambiente = 'producao', valor_servico = '10.00',
                aliquota_iss = '2.00', iss_retido = '0', item_lista_servico = '01.06',
                discriminacao = 'Consultoria em Tecnologia da Informacao - Teste de Transmissao',
                url_pdf = '$urlPdfEsc', xml_envio = '$xmlEnvioEsc', xml_retorno = '$xmlRetornoEsc',
                status = 'concluido', data_emissao = NOW()
                {$extraUpdate}
                WHERE id_emissao = '$idEmissao'";
        } else {
            $extraCols = $hasExtraCols ? ", provider, chave_nfse, url_visualizacao_nacional" : "";
            $extraVals = $hasExtraCols ? ", 'nacional', '$chaveEsc', '$urlPdfEsc'" : "";
            $qSave = "INSERT INTO NfseEmissoes (
                id_fatura, numero_rps, serie_rps, numero_nota, codigo_verificacao, ambiente,
                valor_servico, aliquota_iss, iss_retido, item_lista_servico, discriminacao,
                url_pdf, xml_envio, xml_retorno, status, data_emissao {$extraCols}
            ) VALUES (
                '$idFaturaEsc', '$numDpsEsc', '$serieDpsEsc', '$numNotaEsc', '$chaveEsc', 'producao',
                '10.00', '2.00', '0', '01.06', 'Consultoria em Tecnologia da Informacao - Teste de Transmissao',
                '$urlPdfEsc', '$xmlEnvioEsc', '$xmlRetornoEsc', 'concluido', NOW() {$extraVals}
            )";
        }

        $resSave = DBExecute($link, $qSave);
        if (!$resSave) {
            $err = mysqli_error($link);
            DBClose($link);
            throw new Exception("Erro MySQL ao gravar NfseEmissoes: " . $err);
        }

        DBExecute($link, "UPDATE Faturas SET possui_nfse = 1, data_emissao_nfse = NOW() WHERE id_fatura = '$idFaturaEsc'");
        DBClose($link);

        $response['success'] = true;
        $response['message'] = "NFS-e nº {$numNota} vinculada com SUCESSO à Fatura #{$idFatura}!";
        echo json_encode($response, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        exit;
    }

    if ($action === 'consultar_cadastro') {
        $ambiente = $_POST['ambiente'] ?? ($configEmissor['ambiente_padrao'] ?? 'producao');
        $endpoint = ($ambiente === 'producao')
            ? 'https://nfse.fazenda.df.gov.br/wsnfsenacional/nfse.asmx'
            : 'https://nfse.issnetonline.com.br/wsnfsenacional/homologacao/nfse.asmx';

        $builder = new \Dinovatech\Modules\Fiscal\Builders\ConsultarDadosCadastraisXmlBuilder();
        $cnpj = trim($_POST['prest_cnpj'] ?? $configEmissor['cnpj']);
        $im = trim($_POST['prest_im'] ?? $configEmissor['inscricao_municipal']);

        $xml = $builder->build($cnpj, $im);

        $soapClient = new \Dinovatech\Modules\Fiscal\Http\SoapClient($certManager, $endpoint);
        $versaoDados = ($ambiente === 'producao') ? '1.01' : '1.00';
        $soapResponse = $soapClient->call('ConsultarDadosCadastrais', $xml, $versaoDados);

        $cadResult = $parser->parseConsultaCadastro($soapResponse['response_body'] ?? '');

        $response['success'] = $cadResult->success;
        $response['message'] = $cadResult->message;
        $response['http_code'] = $soapResponse['http_code'] ?? 0;
        $response['envelope_soap'] = $soapResponse['request_envelope'] ?? '';
        $response['response_xml'] = $soapResponse['response_body'] ?? '';
        $response['erros'] = $cadResult->erros;
        $response['cadastro'] = [
            'cnpj' => $cadResult->cnpj,
            'im' => $cadResult->im,
            'status' => $cadResult->statusCadastro,
            'razao_social' => $cadResult->razaoSocial,
            'nome_fantasia' => $cadResult->nomeFantasia,
            'endereco' => "{$cadResult->logradouro}, {$cadResult->bairro} - {$cadResult->codigoMunicipio}/{$cadResult->uf} CEP: {$cadResult->cep}",
            'emite_nfse' => $cadResult->emiteNfse,
            'optante_simples' => $cadResult->optanteSimples,
            'data_simples' => $cadResult->dataSimples,
            'optante_mei' => $cadResult->optanteMei,
            'atividades' => $cadResult->atividades,
            'total_atividades' => count($cadResult->atividades),
            'total_vigentes' => count($cadResult->atividadesVigentes)
        ];

        echo json_encode($response, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        exit;
    }

    // Captura parâmetros da requisição de teste
    $ambiente = $_POST['ambiente'] ?? ($configEmissor['ambiente_padrao'] ?? 'producao'); // homologacao | producao
    $versaoSchema = $_POST['versao_schema'] ?? ($ambiente === 'producao' ? '1.01' : '1.00');
    $envelopeFormat = $_POST['envelope_format'] ?? 'raw'; // cdata | entities | raw
    $envelopeNamespace = $_POST['envelope_namespace'] ?? 'default_ns'; // default_ns | prefixed_ns

    // Dados da DPS
    $serieDps = trim($_POST['serie_dps'] ?? '15');
    $numDps = (int)($_POST['numero_dps'] ?? time() % 100000);
    $tpAmb = $_POST['tp_amb'] ?? ($ambiente === 'producao' ? '1' : '2');
    $dCompet = $_POST['d_compet'] ?? date('Y-m-d');
    if ($dCompet > date('Y-m-d')) {
        $dCompet = date('Y-m-d');
    }
    $dhEmi = $_POST['dh_emi'] ?? date('Y-m-d\TH:i:sP');
    $verAplic = $_POST['ver_aplic'] ?? 'Dinovatech_1.0';
    $tpEmit = $_POST['tp_emit'] ?? '1';
    $cLocEmi = $_POST['c_loc_emi'] ?? '5300108';

    // Prestador
    $prestCnpj = preg_replace('/\D/', '', $_POST['prest_cnpj'] ?? $configEmissor['cnpj']);
    $prestIm = preg_replace('/\D/', '', $_POST['prest_im'] ?? $configEmissor['inscricao_municipal']);
    $opSimpNac = $_POST['op_simp_nac'] ?? ($configEmissor['optante_simples'] ? '3' : '1');
    $regEspTrib = $_POST['reg_esp_trib'] ?? '0';

    // Tomador
    $tomaCpfCnpj = preg_replace('/\D/', '', $_POST['toma_cpf_cnpj'] ?? '01691128104');
    $tomaNome = trim($_POST['toma_nome'] ?? 'DAVI NUNES DE FRANCA');
    $tomaLogradouro = trim($_POST['toma_logradouro'] ?? 'Qi 24');
    $tomaNumero = trim($_POST['toma_numero'] ?? '1');
    $tomaBairro = trim($_POST['toma_bairro'] ?? 'Taguatinga Norte');
    $tomaCmun = $_POST['toma_cmun'] ?? '5300108';
    $tomaCep = preg_replace('/\D/', '', $_POST['toma_cep'] ?? '72135902');
    $tomaFone = preg_replace('/\D/', '', $_POST['toma_fone'] ?? '61996757676');
    $tomaEmail = trim($_POST['toma_email'] ?? 'davi.nunes@gmail.com');

    // Serviço
    $cLocPrestacao = $_POST['c_loc_prestacao'] ?? '5300108';
    $cTribNac = preg_replace('/\D/', '', $_POST['c_trib_nac'] ?? '010601');
    $cTribMun = preg_replace('/\D/', '', $_POST['c_trib_mun'] ?? '106');
    $cNbs = preg_replace('/\D/', '', $_POST['c_nbs'] ?? '115011000');
    $xDescServ = trim($_POST['x_desc_serv'] ?? 'Consultoria em Tecnologia da Informacao');

    // Valores
    $vServ = (float)($_POST['v_serv'] ?? 10.00);
    $tribISSQN = $_POST['trib_issqn'] ?? '1';
    $tpRetISSQN = $_POST['tp_ret_issqn'] ?? '1';
    $pAliq = (float)($_POST['p_aliq'] ?? 2.00);

    // IBS/CBS
    $usarIbsCbs = isset($_POST['usar_ibs_cbs']) ? ($_POST['usar_ibs_cbs'] == '1') : ($versaoSchema === '1.01');
    $cIndOp = $_POST['c_ind_op'] ?? '100301';
    $cstIbsCbs = $_POST['cst_ibs_cbs'] ?? '000';
    $classTrib = $_POST['class_trib'] ?? '000001';

    // Monta Objeto NfseData
    $data = new NfseData();
    $data->ambiente = $ambiente;
    $data->serie = $serieDps;
    $data->numero = $numDps;
    $data->dataCompetencia = $dCompet;
    $data->prestadorCnpj = $prestCnpj;
    $data->prestadorInscricaoMunicipal = $prestIm;
    $data->prestadorMunicipioIbge = $cLocEmi;
    $data->prestadorOptanteSimples = ($opSimpNac === '3' || $opSimpNac === '2');

    $data->tomadorCpfCnpj = $tomaCpfCnpj;
    $data->tomadorRazaoSocial = $tomaNome;
    $data->tomadorLogradouro = $tomaLogradouro;
    $data->tomadorNumero = $tomaNumero;
    $data->tomadorBairro = $tomaBairro;
    $data->tomadorMunicipioIbge = $tomaCmun;
    $data->tomadorCep = $tomaCep;
    $data->tomadorTelefone = $tomaFone;
    $data->tomadorEmail = $tomaEmail;

    $data->municipioPrestacaoIbge = $cLocPrestacao;
    $data->codigoTributacaoNacional = $cTribNac;
    $data->codigoTributacaoMunicipal = $cTribMun;
    $data->codigoNbs = $cNbs;
    $data->discriminacao = $xDescServ;
    $data->valorServico = $vServ;
    $data->tributacaoIssqn = (int)$tribISSQN;
    $data->issRetido = ($tpRetISSQN === '2');
    $data->aliquotaIss = $pAliq;
    $data->indicadorOperacao = $cIndOp;
    $data->cstIbsCbs = $cstIbsCbs;
    $data->classificacaoTribIbsCbs = $classTrib;

    // 1. Gera ID de 45 caracteres
    $dpsId = DpsIdGenerator::generateDpsId(
        $cLocEmi,
        $prestCnpj,
        $serieDps,
        $numDps
    );
    $response['dps_id'] = $dpsId;

    // 2. Monta o XML da DPS
    $builder = new DpsXmlBuilder();
    $rawXml = $builder->build($data, $dpsId);
    $response['raw_xml'] = $rawXml;

    // 3. Assina o nó <infDPS Id="...">
    $signedXml = $signer->sign($rawXml, $dpsId);
    $response['signed_xml'] = $signedXml;

    // 4. Monta o Envelope SOAP personalizado para o teste
    $prologoCabecOption = $_POST['prologo_cabecalho'] ?? 'sem_prologo';
    $prologoXml = ($prologoCabecOption === 'com_prologo') ? "<?xml version=\"1.0\" encoding=\"UTF-8\"?>" : "";
    $cabecalhoXml = "{$prologoXml}<cabecalho versao=\"{$versaoSchema}\" xmlns=\"http://www.sped.fazenda.gov.br/nfse\"><versaoDados>{$versaoSchema}</versaoDados></cabecalho>";

    if ($envelopeFormat === 'entities') {
        $cabecBody = htmlspecialchars($cabecalhoXml, ENT_XML1, 'UTF-8');
        $dadosBody = htmlspecialchars($signedXml, ENT_XML1, 'UTF-8');
    } elseif ($envelopeFormat === 'raw') {
        $cabecBody = str_replace(['<?xml version="1.0" encoding="UTF-8"?>', '<?xml version="1.0" encoding="utf-8"?>'], '', $cabecalhoXml);
        $dadosBody = str_replace(['<?xml version="1.0" encoding="UTF-8"?>', '<?xml version="1.0" encoding="utf-8"?>'], '', $signedXml);
    } else {
        $cabecBody = "<![CDATA[{$cabecalhoXml}]]>";
        $dadosBody = "<![CDATA[{$signedXml}]]>";
    }

    if ($envelopeNamespace === 'prefixed_ns') {
        $methodBlock = "<nfse:GerarNfse>
      <nfse:nfseCabecMsg>{$cabecBody}</nfse:nfseCabecMsg>
      <nfse:nfseDadosMsg>{$dadosBody}</nfse:nfseDadosMsg>
    </nfse:GerarNfse>";
    } else {
        $methodBlock = "<GerarNfse xmlns=\"http://www.sped.fazenda.gov.br/nfse\">
      <nfseCabecMsg>{$cabecBody}</nfseCabecMsg>
      <nfseDadosMsg>{$dadosBody}</nfseDadosMsg>
    </GerarNfse>";
    }

    $soapEnvelope = <<<XML
<soapenv:Envelope xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/" xmlns:nfse="http://www.sped.fazenda.gov.br/nfse">
  <soapenv:Header/>
  <soapenv:Body>
    {$methodBlock}
  </soapenv:Body>
</soapenv:Envelope>
XML;

    if ($action === 'consultar_disponivel') {
        $builderDisp = new \Dinovatech\Modules\Fiscal\Builders\ConsultarDpsDisponivelXmlBuilder();
        $xmlDisp = $builderDisp->build($prestCnpj, $prestIm, 1, $serieDps, $numDps);
        $response['raw_xml'] = $xmlDisp;
        $response['signed_xml'] = $xmlDisp;

        $methodBlockDisp = "<ConsultarDpsDisponivel xmlns=\"http://www.sped.fazenda.gov.br/nfse\">
      <nfseCabecMsg>{$cabecalhoXml}</nfseCabecMsg>
      <nfseDadosMsg>{$xmlDisp}</nfseDadosMsg>
    </ConsultarDpsDisponivel>";

        $soapEnvelope = <<<XML
<soapenv:Envelope xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/" xmlns:nfse="http://www.sped.fazenda.gov.br/nfse">
  <soapenv:Header/>
  <soapenv:Body>
    {$methodBlockDisp}
  </soapenv:Body>
</soapenv:Envelope>
XML;
        $response['envelope_soap'] = $soapEnvelope;
        $soapAction = "http://www.sped.fazenda.gov.br/nfse/ConsultarDpsDisponivel";
    } elseif ($action === 'consultar_dps') {
        $builderDps = new \Dinovatech\Modules\Fiscal\Builders\ConsultarNfseDpsXmlBuilder();
        $xmlDps = $builderDps->build($prestCnpj, $prestIm, $numDps, $serieDps);
        $response['raw_xml'] = $xmlDps;
        $response['signed_xml'] = $xmlDps;

        $methodBlockDps = "<ConsultarNfseDps xmlns=\"http://www.sped.fazenda.gov.br/nfse\">
      <nfseCabecMsg>{$cabecalhoXml}</nfseCabecMsg>
      <nfseDadosMsg>{$xmlDps}</nfseDadosMsg>
    </ConsultarNfseDps>";

        $soapEnvelope = <<<XML
<soapenv:Envelope xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/" xmlns:nfse="http://www.sped.fazenda.gov.br/nfse">
  <soapenv:Header/>
  <soapenv:Body>
    {$methodBlockDps}
  </soapenv:Body>
</soapenv:Envelope>
XML;
        $response['envelope_soap'] = $soapEnvelope;
        $soapAction = "http://www.sped.fazenda.gov.br/nfse/ConsultarNfseDps";
    } elseif ($action === 'consultar_url') {
        $numNota = $_POST['numero_nota'] ?? '54';
        $builderUrl = new \Dinovatech\Modules\Fiscal\Builders\ConsultarUrlXmlBuilder();
        $rawXml = $builderUrl->build($prestCnpj, $prestIm, $numNota);

        $response['raw_xml'] = $rawXml;
        $response['signed_xml'] = $rawXml;

        $methodBlockUrl = "<ConsultarUrlNfse xmlns=\"http://www.sped.fazenda.gov.br/nfse\">
      <nfseCabecMsg>{$cabecalhoXml}</nfseCabecMsg>
      <nfseDadosMsg>{$rawXml}</nfseDadosMsg>
    </ConsultarUrlNfse>";

        $soapEnvelope = <<<XML
<soapenv:Envelope xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/" xmlns:nfse="http://www.sped.fazenda.gov.br/nfse">
  <soapenv:Header/>
  <soapenv:Body>
    {$methodBlockUrl}
  </soapenv:Body>
</soapenv:Envelope>
XML;
        $response['envelope_soap'] = $soapEnvelope;
        $soapAction = "http://www.sped.fazenda.gov.br/nfse/ConsultarUrlNfse";
    } else {
        $response['envelope_soap'] = $soapEnvelope;
        $soapAction = "http://www.sped.fazenda.gov.br/nfse/GerarNfse";
    }

    // Se o pedido for apenas preview, encerra com os XMLs gerados
    if ($action === 'preview') {
        $response['success'] = true;
        $response['message'] = "DPS e Envelope SOAP gerados com sucesso para visualização!";
        echo json_encode($response, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        exit;
    }

    // 5. Transmissão real via cURL
    $endpoint = ($ambiente === 'producao')
        ? 'https://nfse.fazenda.df.gov.br/wsnfsenacional/nfse.asmx'
        : 'https://nfse.issnetonline.com.br/wsnfsenacional/homologacao/nfse.asmx';

    $tlsFiles = $certManager->getTlsFiles();

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $endpoint);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $soapEnvelope);
    curl_setopt($ch, CURLOPT_TIMEOUT, 60);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: text/xml; charset=utf-8',
        'SOAPAction: "' . $soapAction . '"',
        'Content-Length: ' . strlen($soapEnvelope),
        'Accept: text/xml, application/xml, */*'
    ]);

    curl_setopt($ch, CURLOPT_SSLCERT, $tlsFiles['cert']);
    curl_setopt($ch, CURLOPT_SSLKEY, $tlsFiles['key']);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
    curl_setopt($ch, CURLOPT_ENCODING, '');

    $responseBody = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError = curl_error($ch);
    curl_close($ch);

    $certManager->cleanup();

    $response['http_code'] = $httpCode;

    if (!empty($curlError)) {
        throw new Exception("Erro cURL mTLS: " . $curlError);
    }

    if (empty($responseBody)) {
        throw new Exception("Servidor não retornou resposta (Código HTTP {$httpCode}).");
    }

    if (!mb_check_encoding($responseBody, 'UTF-8')) {
        $responseBody = mb_convert_encoding($responseBody, 'UTF-8', 'ISO-8859-1, Windows-1252');
    }

    if ($responseBody && strpos($responseBody, '&lt;') !== false && strpos($responseBody, '&gt;') !== false) {
        $unwrapped = htmlspecialchars_decode($responseBody);
        if (strpos($unwrapped, '<') !== false) {
            $responseBody = $unwrapped;
        }
    }

    $response['response_xml'] = $responseBody;

    // Interpreta o retorno
    if ($action === 'consultar_disponivel') {
        $parsedRes = $parser->parseConsultaDps($responseBody);
        $response['success'] = $parsedRes->success;
        $response['message'] = $parsedRes->message;
        $response['erros'] = $parsedRes->erros;
        $response['details'] = !empty($parsedRes->erros) ? implode("\n", $parsedRes->erros) : 'Consulta processada pelo WebService.';
        if ($parsedRes->encontrada) {
            $response['numero_nota'] = $parsedRes->numeroNota;
            $response['chave_nfse'] = $parsedRes->chaveNfse;
            $response['codigo_verificacao'] = $parsedRes->codigoVerificacao;
        }
    } elseif ($action === 'consultar_dps') {
        $parsedRes = $parser->parseConsultaDps($responseBody);
        $response['success'] = $parsedRes->success;
        $response['message'] = $parsedRes->message;
        $response['erros'] = $parsedRes->erros;
        $response['details'] = !empty($parsedRes->erros) ? implode("\n", $parsedRes->erros) : ($parsedRes->encontrada ? "NFS-e Localizada!\nNúmero: {$parsedRes->numeroNota}\nChave: {$parsedRes->chaveNfse}\nCód Verificação: {$parsedRes->codigoVerificacao}" : 'NFS-e não encontrada para esta DPS.');
        if ($parsedRes->encontrada) {
            $response['numero_nota'] = $parsedRes->numeroNota;
            $response['chave_nfse'] = $parsedRes->chaveNfse;
            $response['codigo_verificacao'] = $parsedRes->codigoVerificacao;
        }
    } elseif ($action === 'consultar_url') {
        $parsedRes = $parser->parseConsultarUrl($responseBody);
        $response['success'] = $parsedRes->success;
        $response['message'] = $parsedRes->message;
        $response['details'] = "URL Visualização: " . ($parsedRes->urlVisualizacao ?: 'N/A') . "\n"
            . "URL Autenticidade: " . ($parsedRes->urlVerificacaoAutenticidade ?: 'N/A') . "\n"
            . "URL Nacional: " . ($parsedRes->urlVisualizacaoNacional ?: 'N/A');
    } else {
        $parsedRes = $parser->parseEmissao($responseBody, $signedXml, $dpsId);
        $response['success'] = $parsedRes->isSuccess();
        $response['message'] = $parsedRes->message;
        $response['details'] = $parsedRes->details;
        $response['erros'] = $parsedRes->erros;

        if ($parsedRes->isSuccess()) {
            $response['numero_nota'] = $parsedRes->numeroNota;
            $response['chave_nfse'] = $parsedRes->chaveNfse;
            $response['codigo_verificacao'] = $parsedRes->codigoVerificacao;
        }
    }

} catch (\Throwable $e) {
    $response['success'] = false;
    $response['message'] = "Exceção no Teste: " . $e->getMessage();
    $response['details'] = "Arquivo: " . $e->getFile() . " (Linha " . $e->getLine() . ")";
    if (isset($certManager)) {
        $certManager->cleanup();
    }
}

echo json_encode($response, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
