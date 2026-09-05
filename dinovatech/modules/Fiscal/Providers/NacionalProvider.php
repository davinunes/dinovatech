<?php
namespace Dinovatech\Modules\Fiscal\Providers;

use Dinovatech\Modules\Fiscal\Contracts\NfseProviderInterface;
use Dinovatech\Modules\Fiscal\DTOs\NfseData;
use Dinovatech\Modules\Fiscal\DTOs\EmissionResult;
use Dinovatech\Modules\Fiscal\DTOs\QueryResult;
use Dinovatech\Modules\Fiscal\DTOs\CancellationResult;
use Dinovatech\Modules\Fiscal\DTOs\UrlResult;
use Dinovatech\Modules\Fiscal\Formatters\DpsIdGenerator;
use Dinovatech\Modules\Fiscal\Security\CertificateManager;
use Dinovatech\Modules\Fiscal\Security\XmlSigner;
use Dinovatech\Modules\Fiscal\Http\SoapClient;
use Dinovatech\Modules\Fiscal\Builders\DpsXmlBuilder;
use Dinovatech\Modules\Fiscal\Builders\ConsultarDpsXmlBuilder;
use Dinovatech\Modules\Fiscal\Builders\ConsultarUrlXmlBuilder;
use Dinovatech\Modules\Fiscal\Builders\CancelarNfseXmlBuilder;
use Dinovatech\Modules\Fiscal\Parsers\NacionalResponseParser;
use Exception;

class NacionalProvider implements NfseProviderInterface
{
    private array $config;
    private $link;
    private ?CertificateManager $certManager = null;
    private NacionalResponseParser $parser;

    public function __construct(array $config, $link)
    {
        $this->config = $config;
        $this->link = $link;
        $this->parser = new NacionalResponseParser();
    }

    public function getProviderName(): string
    {
        return 'nacional';
    }

    public function emitir(NfseData $data): EmissionResult
    {
        $result = new EmissionResult();

        try {
            $certManager = $this->getCertificateManager();
            $signer = new XmlSigner($certManager);
            $builder = new DpsXmlBuilder();

            // 1. Gera o identificador oficial de 45 caracteres da DPS
            $dpsId = DpsIdGenerator::generateDpsId(
                $data->prestadorMunicipioIbge ?: '5300108',
                $data->prestadorCnpj,
                $data->serie,
                $data->numero
            );

            // 2. Monta o XML da DPS
            $rawXml = $builder->build($data, $dpsId);

            // 3. Assina o nó <infDPS Id="...">
            $signedXml = $signer->sign($rawXml, $dpsId);

            // 4. Envia via SOAP
            $endpoint = $this->getEndpointUrl($data->ambiente);
            $soapClient = new SoapClient($certManager, $endpoint);

            $versaoDados = ($data->ambiente === 'producao') ? '1.01' : '1.00';
            $soapResponse = $soapClient->call('GerarNfse', $signedXml, $versaoDados);

            if (!empty($soapResponse['curl_error'])) {
                throw new Exception("Falha de conexão com o WebService da NFS-e: " . $soapResponse['curl_error']);
            }

            $responseXml = $soapResponse['response_body'];
            if (empty($responseXml)) {
                $httpCode = $soapResponse['http_code'] ?? 0;
                throw new Exception("Servidor da NFS-e não retornou resposta (Código HTTP {$httpCode}).");
            }

            // 5. Interpreta o retorno
            $result = $this->parser->parseEmissao($responseXml, $signedXml, $dpsId);
            $result->envelopeEnvio = $soapResponse['request_envelope'] ?? '';

            // 6. AUTO-RECUPERAÇÃO / RESILIÊNCIA: Se não confirmou imediatamente, consulta a DPS
            if (!$result->isSuccess()) {
                $checkQuery = $this->consultarPorDocumento($data->serie, $data->numero);
                if ($checkQuery->encontrada && !empty($checkQuery->numeroNota)) {
                    $result->success = true;
                    $result->status = 'concluido';
                    $result->numeroNota = $checkQuery->numeroNota;
                    $result->chaveNfse = $checkQuery->chaveNfse;
                    $result->codigoVerificacao = $checkQuery->codigoVerificacao;
                    $result->message = 'NFS-e confirmada com sucesso via checagem da DPS!';
                }
            }

            // 7. Se emitida com sucesso, busca URLs oficiais de visualização
            if ($result->isSuccess()) {
                try {
                    $urlRes = $this->consultarUrl($result->numeroNota ?: '0', $data->serie, $data->numero);
                    if ($urlRes->success) {
                        $result->urlVisualizacao = $urlRes->urlVisualizacao;
                        $result->urlVisualizacaoNacional = $urlRes->urlVisualizacaoNacional;
                    }
                } catch (Exception $e) {
                    // Prossegue mesmo se a consulta de URL falhar momentaneamente
                }
            }

        } catch (\Throwable $e) {
            $result->success = false;
            $result->status = 'erro';
            $result->message = 'Exceção na emissão Nacional: ' . $e->getMessage();
            $result->details = "Erro em " . $e->getFile() . ":" . $e->getLine() . "\n" . $e->getTraceAsString();
        } finally {
            if (isset($certManager)) {
                $certManager->cleanup();
            }
        }

        return $result;
    }

    public function consultarPorDocumento(string $serie, int $numero): QueryResult
    {
        try {
            $certManager = $this->getCertificateManager();
            $builder = new ConsultarDpsXmlBuilder();
            $ambiente = $this->config['ambiente_padrao'] ?? 'homologacao';
            $endpoint = $this->getEndpointUrl($ambiente);
            $soapClient = new SoapClient($certManager, $endpoint);

            $xml = $builder->build(
                $serie,
                $numero,
                $this->config['cnpj'] ?? '',
                $this->config['inscricao_municipal'] ?? ''
            );

            $res = $soapClient->call('ConsultarNfseDps', $xml, '1.00');
            return $this->parser->parseConsultaDps($res['response_body']);
        } catch (Exception $e) {
            $q = new QueryResult();
            $q->success = false;
            $q->message = "Erro ao consultar DPS: " . $e->getMessage();
            return $q;
        } finally {
            if (isset($certManager)) {
                $certManager->cleanup();
            }
        }
    }

    public function consultarDpsDisponivel(int $pagina = 1, ?string $serie = null, ?int $numero = null): QueryResult
    {
        try {
            $certManager = $this->getCertificateManager();
            $builder = new \Dinovatech\Modules\Fiscal\Builders\ConsultarDpsDisponivelXmlBuilder();
            $ambiente = $this->config['ambiente_padrao'] ?? 'homologacao';
            $endpoint = $this->getEndpointUrl($ambiente);
            $soapClient = new SoapClient($certManager, $endpoint);

            $xml = $builder->build(
                $this->config['cnpj'] ?? '',
                $this->config['inscricao_municipal'] ?? '',
                $pagina,
                $serie,
                $numero
            );

            $res = $soapClient->call('ConsultarDpsDisponivel', $xml, '1.00');
            return $this->parser->parseConsultaDps($res['response_body']);
        } catch (Exception $e) {
            $q = new QueryResult();
            $q->success = false;
            $q->message = "Erro ao consultar DPS disponíveis: " . $e->getMessage();
            return $q;
        } finally {
            if (isset($certManager)) {
                $certManager->cleanup();
            }
        }
    }

    public function consultarUrl(string $numeroNota, ?string $serie = null, ?int $numeroDocumento = null): UrlResult
    {
        try {
            $signer = new XmlSigner($certManager);
            $reqId = "ConsultarUrl1";
            $rawXml = $builder->build(
                $this->config['cnpj'] ?? '',
                $this->config['inscricao_municipal'] ?? '',
                $numeroNota,
                $serie,
                $numeroDocumento,
                $reqId
            );

            $signedXml = $signer->sign($rawXml, $reqId);
            $res = $soapClient->call('ConsultarUrlNfse', $signedXml, '1.00');
            return $this->parser->parseConsultarUrl($res['response_body']);
        } catch (Exception $e) {
            $u = new UrlResult();
            $u->success = false;
            $u->message = "Erro ao consultar URLs: " . $e->getMessage();
            return $u;
        } finally {
            if (isset($certManager)) {
                $certManager->cleanup();
            }
        }
    }

    public function cancelar(string $identificadorNota, int $codigoMotivo, string $justificativa): CancellationResult
    {
        try {
            $certManager = $this->getCertificateManager();
            $signer = new XmlSigner($certManager);
            $builder = new CancelarNfseXmlBuilder();
            $ambiente = $this->config['ambiente_padrao'] ?? 'homologacao';
            $endpoint = $this->getEndpointUrl($ambiente);
            $soapClient = new SoapClient($certManager, $endpoint);

            $dados = $builder->build(
                $identificadorNota,
                $this->config['cnpj'] ?? '',
                $codigoMotivo,
                $justificativa,
                $ambiente
            );

            $signedXml = $signer->sign($dados['xml'], $dados['preId']);
            $res = $soapClient->call('CancelarNfse', $signedXml, '1.00');

            return $this->parser->parseCancelamento($res['response_body'], $signedXml);
        } catch (Exception $e) {
            $c = new CancellationResult();
            $c->success = false;
            $c->message = "Erro ao solicitar cancelamento: " . $e->getMessage();
            return $c;
        } finally {
            if (isset($certManager)) {
                $certManager->cleanup();
            }
        }
    }

    private function getCertificateManager(): CertificateManager
    {
        if ($this->certManager) {
            return $this->certManager;
        }

        $pfxContent = null;
        if (!empty($this->config['certificado_pfx_base64'])) {
            $pfxContent = $this->config['certificado_pfx_base64'];
        } elseif (!empty($this->config['caminho_certificado']) && file_exists($this->config['caminho_certificado'])) {
            $pfxContent = file_get_contents($this->config['caminho_certificado']);
        }

        if (!$pfxContent) {
            throw new Exception("Certificado digital A1 não configurado.");
        }

        $this->certManager = new CertificateManager($pfxContent, $this->config['senha_certificado'] ?? '');
        return $this->certManager;
    }

    private function getEndpointUrl(string $ambiente): string
    {
        // Endpoints operacionais confirmados (soap:address do WSDL aponta para /nfse.asmx,
        // mas o serviço real está em /wsnfsenacional/).
        return ($ambiente === 'producao')
            ? 'https://nfse.fazenda.df.gov.br/wsnfsenacional/nfse.asmx'
            : 'https://nfse.issnetonline.com.br/wsnfsenacional/homologacao/nfse.asmx';
    }
}
