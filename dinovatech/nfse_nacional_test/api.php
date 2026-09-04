<?php
// dinovatech/nfse_nacional_test/api.php - Backend de Testes Interativo para NFS-e Padrão Nacional
header('Content-Type: application/json; charset=utf-8');

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

    // Captura parâmetros da requisição de teste
    $ambiente = $_POST['ambiente'] ?? ($configEmissor['ambiente_padrao'] ?? 'homologacao'); // homologacao | producao
    $versaoSchema = $_POST['versao_schema'] ?? ($ambiente === 'producao' ? '1.01' : '1.00');
    $envelopeFormat = $_POST['envelope_format'] ?? 'cdata'; // cdata | entities
    $envelopeNamespace = $_POST['envelope_namespace'] ?? 'default_ns'; // default_ns | prefixed_ns

    // Dados da DPS
    $serieDps = trim($_POST['serie_dps'] ?? '3');
    $numDps = (int)($_POST['numero_dps'] ?? time() % 100000);
    $tpAmb = $_POST['tp_amb'] ?? ($ambiente === 'producao' ? '1' : '2');
    $dCompet = $_POST['d_compet'] ?? date('Y-m-d');
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
    $cIndOp = $_POST['c_ind_op'] ?? '050101';
    $cstIbsCbs = $_POST['cst_ibs_cbs'] ?? '000';
    $classTrib = $_POST['class_trib'] ?? '000000';

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
    $cabecalhoXml = "<?xml version=\"1.0\" encoding=\"UTF-8\"?><cabecalho versao=\"{$versaoSchema}\" xmlns=\"http://www.sped.fazenda.gov.br/nfse\"><versaoDados>{$versaoSchema}</versaoDados></cabecalho>";

    if ($envelopeFormat === 'entities') {
        $cabecBody = htmlspecialchars($cabecalhoXml, ENT_XML1, 'UTF-8');
        $dadosBody = htmlspecialchars($signedXml, ENT_XML1, 'UTF-8');
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

        if ($envelopeFormat === 'entities') {
            $cabecBody = htmlspecialchars($cabecalhoXml, ENT_XML1, 'UTF-8');
            $dadosBody = htmlspecialchars($xmlDisp, ENT_XML1, 'UTF-8');
        } else {
            $cabecBody = "<![CDATA[{$cabecalhoXml}]]>";
            $dadosBody = "<![CDATA[{$xmlDisp}]]>";
        }

        $soapEnvelope = <<<XML
<soapenv:Envelope xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/">
  <soapenv:Header/>
  <soapenv:Body>
    <ConsultarDpsDisponivel xmlns="http://www.sped.fazenda.gov.br/nfse">
      <nfseCabecMsg>{$cabecBody}</nfseCabecMsg>
      <nfseDadosMsg>{$dadosBody}</nfseDadosMsg>
    </ConsultarDpsDisponivel>
  </soapenv:Body>
</soapenv:Envelope>
XML;
        $response['envelope_soap'] = $soapEnvelope;
        $soapAction = "http://www.sped.fazenda.gov.br/nfse/ConsultarDpsDisponivel";
    } else {
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

} catch (\Throwable $e) {
    $response['success'] = false;
    $response['message'] = "Exceção no Teste: " . $e->getMessage();
    $response['details'] = "Arquivo: " . $e->getFile() . " (Linha " . $e->getLine() . ")";
    if (isset($certManager)) {
        $certManager->cleanup();
    }
}

echo json_encode($response, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
