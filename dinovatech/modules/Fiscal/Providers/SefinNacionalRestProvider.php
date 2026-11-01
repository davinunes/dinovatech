<?php
namespace Dinovatech\Modules\Fiscal\Providers;

use Dinovatech\Modules\Fiscal\Contracts\NfseProviderInterface;
use Dinovatech\Modules\Fiscal\DTOs\NfseData;
use Dinovatech\Modules\Fiscal\DTOs\EmissionResult;
use Dinovatech\Modules\Fiscal\DTOs\QueryResult;
use Dinovatech\Modules\Fiscal\DTOs\CancellationResult;
use Dinovatech\Modules\Fiscal\DTOs\UrlResult;
use Dinovatech\Modules\Fiscal\DTOs\CadastroResult;
use Dinovatech\Modules\Fiscal\Formatters\DpsIdGenerator;
use Dinovatech\Modules\Fiscal\Security\CertificateManager;
use Dinovatech\Modules\Fiscal\Security\XmlSigner;
use Dinovatech\Modules\Fiscal\Http\RestClient;
use Dinovatech\Modules\Fiscal\Builders\DpsXmlBuilder;
use Dinovatech\Modules\Fiscal\Builders\CancelarNfseXmlBuilder;
use Exception;

/**
 * Provedor de integração direta via API REST (JSON) com o Emissor Público Nacional da NFS-e (Sefin Nacional / RFB).
 * 
 * Protocolo: HTTPS / RESTful com autenticação mútua TLS (mTLS) via certificado digital A1 ICP-Brasil.
 * Carga útil: XML da DPS assinado digitalmente, compactado em GZip e codificado em Base64.
 */
class SefinNacionalRestProvider implements NfseProviderInterface
{
    private array $config;
    private $link;
    private ?CertificateManager $certManager = null;

    public function __construct(array $config, $link = null)
    {
        $this->config = $config;
        $this->link = $link;
    }

    public function getProviderName(): string
    {
        return 'sefin_nacional';
    }

    public function emitir(NfseData $data): EmissionResult
    {
        $result = new EmissionResult();

        try {
            $certManager = $this->getCertificateManager();
            $signer = new XmlSigner($certManager);
            $builder = new DpsXmlBuilder();

            // 1. Gera o identificador oficial de 45 caracteres da DPS
            $cLocEmi = $data->prestadorMunicipioIbge ?: '5300108';
            $dpsId = DpsIdGenerator::generateDpsId(
                $cLocEmi,
                $data->prestadorCnpj,
                $data->serie,
                $data->numero
            );
            $result->idDps = $dpsId;

            // 2. Monta o XML da DPS puro com raiz <DPS> (sem wrapper SOAP <GerarNfseEnvio>)
            $rawXml = $builder->buildDps($data, $dpsId);

            // 3. Assina o nó <infDPS Id="..."> com XMLDSig (RSA-SHA1 + C14N)
            $signedXml = $signer->sign($rawXml, $dpsId);
            $result->xmlEnvio = $signedXml;

            // 4. Compacta o XML em GZip e codifica em Base64
            $gzippedXml = gzencode($signedXml, 9);
            if ($gzippedXml === false) {
                throw new Exception("Falha ao compactar o XML da DPS com GZip.");
            }
            $dpsXmlGZipB64 = base64_encode($gzippedXml);

            // 5. Envia via REST POST /nfse
            $baseUrl = $this->getBaseUrl($data->ambiente);
            $client = new RestClient($certManager, $baseUrl);

            $payload = [
                'dpsXmlGZipB64' => $dpsXmlGZipB64
            ];

            $res = $client->request('POST', '/nfse', $payload);

            if (!empty($res['curl_error'])) {
                throw new Exception("Falha de conexão com a API Sefin Nacional: " . $res['curl_error']);
            }

            $httpCode = $res['http_code'];
            $json = $res['json'];

            // 6. Interpreta o retorno
            if ($httpCode === 201 && is_array($json)) {
                // Sucesso na emissão da NFS-e
                $result->success = true;
                $result->status = 'concluido';
                $result->message = 'NFS-e emitida com sucesso no Emissor Público Nacional (REST).';
                $result->chaveNfse = $json['chaveAcesso'] ?? $json['ChaveAcesso'] ?? '';

                $nfseGzipB64 = $json['nfseXmlGZipB64'] ?? $json['NfseXmlGZipB64'] ?? '';
                if (!empty($nfseGzipB64)) {
                    $decodedNfse = base64_decode($nfseGzipB64);
                    $decompressedXml = @gzdecode($decodedNfse);
                    if ($decompressedXml !== false) {
                        $result->xmlRetorno = $decompressedXml;

                        // Extrai número da nota (<nNFSe> ou <nDFSe>)
                        if (preg_match('/<(?:\w+:)?nNFSe\b[^>]*>(.*?)<\/(?:\w+:)?nNFSe>/si', $decompressedXml, $m) ||
                            preg_match('/<(?:\w+:)?nDFSe\b[^>]*>(.*?)<\/(?:\w+:)?nDFSe>/si', $decompressedXml, $m)) {
                            $result->numeroNota = trim($m[1]);
                        }

                        // Extrai código de verificação
                        if (preg_match('/<(?:\w+:)?cVerif\b[^>]*>(.*?)<\/(?:\w+:)?cVerif>/si', $decompressedXml, $m)) {
                            $result->codigoVerificacao = trim($m[1]);
                        }
                    } else {
                        $result->xmlRetorno = $res['response_body'];
                    }
                } else {
                    $result->xmlRetorno = $res['response_body'];
                }

                // URLs oficiais de visualização
                if (!empty($result->chaveNfse)) {
                    $cleanChave = preg_replace('/^NFS/i', '', trim($result->chaveNfse));
                    $result->urlVisualizacaoNacional = "https://www.nfse.gov.br/EmissorNacional/Notas/Visualizar/Index/{$cleanChave}";
                    $result->urlVisualizacao = $result->urlVisualizacaoNacional;
                }
            } else {
                // Erro ou rejeição
                $result->success = false;
                $result->status = 'erro';
                $result->xmlRetorno = $res['response_body'];

                $erros = [];
                $rawErros = [];
                if (is_array($json)) {
                    if (!empty($json['erros']) && is_array($json['erros'])) {
                        $rawErros = $json['erros'];
                    } elseif (!empty($json['Erros']) && is_array($json['Erros'])) {
                        $rawErros = $json['Erros'];
                    }
                }

                if (!empty($rawErros)) {
                    foreach ($rawErros as $err) {
                        if (is_string($err)) {
                            $erros[] = $err;
                            continue;
                        }
                        $cod = $err['codigo'] ?? $err['Codigo'] ?? $err['code'] ?? '';
                        $desc = $err['descricao'] ?? $err['Descricao'] ?? $err['description'] ?? $err['message'] ?? '';
                        $comp = $err['complemento'] ?? $err['Complemento'] ?? '';
                        $prefixo = $cod ? "[$cod] " : "";
                        $sufixo = $comp ? " ($comp)" : "";
                        $linha = trim("{$prefixo}{$desc}{$sufixo}");
                        if (!empty($linha)) {
                            $erros[] = $linha;
                        }
                    }
                }

                if (!empty($erros)) {
                    $result->erros = $erros;
                    $result->message = implode("; ", $erros);
                    $result->details = implode("\n", $erros);
                } else {
                    $result->message = "Erro HTTP {$httpCode} na API Sefin Nacional.";
                    $result->details = $res['response_body'];
                }

                // 7. AUTO-RECUPERAÇÃO: Se falhou por timeout ou erro não definitivo, consulta a DPS
                $checkQuery = $this->consultarPorDocumento($data->serie, $data->numero);
                if ($checkQuery->encontrada && !empty($checkQuery->numeroNota)) {
                    $result->success = true;
                    $result->status = 'concluido';
                    $result->numeroNota = $checkQuery->numeroNota;
                    $result->chaveNfse = $checkQuery->chaveNfse;
                    $result->codigoVerificacao = $checkQuery->codigoVerificacao;
                    $result->message = 'NFS-e confirmada com sucesso via checagem da DPS na API Nacional!';
                    if (!empty($result->chaveNfse)) {
                        $cleanChave = preg_replace('/^NFS/i', '', trim($result->chaveNfse));
                        $result->urlVisualizacaoNacional = "https://www.nfse.gov.br/EmissorNacional/Notas/Visualizar/Index/{$cleanChave}";
                        $result->urlVisualizacao = $result->urlVisualizacaoNacional;
                    }
                }
            }
        } catch (\Throwable $e) {
            $result->success = false;
            $result->status = 'erro';
            $result->message = 'Exceção na emissão Sefin Nacional REST: ' . $e->getMessage();
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
        $q = new QueryResult();

        try {
            $certManager = $this->getCertificateManager();
            $cLocEmi = $this->config['codigo_municipio'] ?: '5300108';
            $cnpj = $this->config['cnpj'] ?? '';

            $dpsId = DpsIdGenerator::generateDpsId($cLocEmi, $cnpj, $serie, $numero);
            $ambiente = $this->config['ambiente_padrao'] ?? 'homologacao';
            $baseUrl = $this->getBaseUrl($ambiente);
            $client = new RestClient($certManager, $baseUrl);

            $res = $client->request('GET', "/dps/{$dpsId}");

            if ($res['http_code'] === 200 && is_array($res['json'])) {
                $q->success = true;
                $q->encontrada = true;
                $q->chaveNfse = $res['json']['chaveAcesso'] ?? $res['json']['ChaveAcesso'] ?? '';

                // Se obteve a chave de acesso, busca a NFS-e completa para obter número e dados
                if (!empty($q->chaveNfse)) {
                    $cleanChave = preg_replace('/^NFS/i', '', trim($q->chaveNfse));
                    $nfseRes = $client->request('GET', "/nfse/{$cleanChave}");
                    $nfseB64 = $nfseRes['json']['nfseXmlGZipB64'] ?? $nfseRes['json']['NfseXmlGZipB64'] ?? '';
                    if ($nfseRes['http_code'] === 200 && is_array($nfseRes['json']) && !empty($nfseB64)) {
                        $decompressedXml = @gzdecode(base64_decode($nfseB64));
                        if ($decompressedXml) {
                            $q->xmlNfse = $decompressedXml;
                            if (preg_match('/<(?:\w+:)?nNFSe\b[^>]*>(.*?)<\/(?:\w+:)?nNFSe>/si', $decompressedXml, $m) ||
                                preg_match('/<(?:\w+:)?nDFSe\b[^>]*>(.*?)<\/(?:\w+:)?nDFSe>/si', $decompressedXml, $m)) {
                                $q->numeroNota = trim($m[1]);
                            }
                            if (preg_match('/<(?:\w+:)?cVerif\b[^>]*>(.*?)<\/(?:\w+:)?cVerif>/si', $decompressedXml, $m)) {
                                $q->codigoVerificacao = trim($m[1]);
                            }
                        }
                    }
                    $q->urlVisualizacao = "https://www.nfse.gov.br/EmissorNacional/Notas/Visualizar/Index/{$cleanChave}";
                    $q->urlVisualizacaoNacional = $q->urlVisualizacao;
                }

                $q->message = "DPS localizada com sucesso no Emissor Nacional.";
            } else {
                $q->success = false;
                $q->encontrada = false;
                $q->message = ($res['http_code'] === 404)
                    ? "DPS não encontrada na base do Emissor Nacional."
                    : "Erro na consulta de DPS (HTTP {$res['http_code']}).";
            }
        } catch (Exception $e) {
            $q->success = false;
            $q->message = "Erro ao consultar DPS via REST: " . $e->getMessage();
        } finally {
            if (isset($certManager)) {
                $certManager->cleanup();
            }
        }

        return $q;
    }

    public function consultarUrl(string $numeroNota, ?string $serie = null, ?int $numeroDocumento = null): UrlResult
    {
        $res = new UrlResult();

        if ($serie !== null && $numeroDocumento !== null) {
            $query = $this->consultarPorDocumento($serie, $numeroDocumento);
            if ($query->encontrada && !empty($query->chaveNfse)) {
                $cleanChave = preg_replace('/^NFS/i', '', trim($query->chaveNfse));
                $res->success = true;
                $res->chaveNfse = $cleanChave;
                $res->urlVisualizacaoNacional = "https://www.nfse.gov.br/EmissorNacional/Notas/Visualizar/Index/{$cleanChave}";
                $res->urlVisualizacao = $res->urlVisualizacaoNacional;
                $res->message = "URL gerada a partir da consulta de DPS.";
                return $res;
            }
        }

        // Se tiver apenas a chave ou número
        if (strlen($numeroNota) >= 40) {
            $cleanChave = preg_replace('/^NFS/i', '', trim($numeroNota));
            $res->success = true;
            $res->chaveNfse = $cleanChave;
            $res->urlVisualizacaoNacional = "https://www.nfse.gov.br/EmissorNacional/Notas/Visualizar/Index/{$cleanChave}";
            $res->urlVisualizacao = $res->urlVisualizacaoNacional;
            $res->message = "URL gerada a partir da chave informada.";
            return $res;
        }

        $res->success = false;
        $res->message = "No padrão nacional REST, a consulta de URL exige a chave de acesso da NFS-e ou identificação da DPS.";
        return $res;
    }

    public function cancelar(string $identificadorNota, int $codigoMotivo, string $justificativa): CancellationResult
    {
        $c = new CancellationResult();

        try {
            $certManager = $this->getCertificateManager();
            $signer = new XmlSigner($certManager);

            $cleanChave = preg_replace('/\D/', '', $identificadorNota);
            if (strlen($cleanChave) < 40) {
                throw new Exception("Cancelamento no Emissor Nacional exige a chave de acesso de 50 dígitos.");
            }

            $cnpj = preg_replace('/\D/', '', $this->config['cnpj'] ?? '');
            $ambiente = $this->config['ambiente_padrao'] ?? 'homologacao';
            $tpAmb = ($ambiente === 'producao') ? '1' : '2';
            $verAplic = 'Dinovatech_1.0';
            $dhEvento = date('Y-m-d\TH:i:sP');

            $preId = DpsIdGenerator::generatePedidoEventoId($cleanChave, '101103');
            $xMotivo = htmlspecialchars(trim($justificativa), ENT_XML1, 'UTF-8');
            if (strlen($xMotivo) < 15) {
                $xMotivo = str_pad($xMotivo, 15, ' ', STR_PAD_RIGHT);
            }

            // XML do evento conforme schema pedRegEvento v1.01
            $rawPedEventoXml = <<<XML
<pedRegEvento versao="1.01" xmlns="http://www.sped.fazenda.gov.br/nfse">
    <infPedReg Id="{$preId}">
        <tpAmb>{$tpAmb}</tpAmb>
        <verAplic>{$verAplic}</verAplic>
        <dhEvento>{$dhEvento}</dhEvento>
        <CNPJAutor>{$cnpj}</CNPJAutor>
        <chNFSe>{$cleanChave}</chNFSe>
        <e101103>
            <xDesc>Solicitação de Análise Fiscal para Cancelamento de NFS-e</xDesc>
            <cMotivo>{$codigoMotivo}</cMotivo>
            <xMotivo>{$xMotivo}</xMotivo>
        </e101103>
    </infPedReg>
</pedRegEvento>
XML;

            $signedXml = $signer->sign($rawPedEventoXml, $preId);
            $c->xmlEnvio = $signedXml;

            $gzipped = gzencode($signedXml, 9);
            if ($gzipped === false) {
                throw new Exception("Falha ao compactar pedido de cancelamento com GZip.");
            }
            $b64 = base64_encode($gzipped);

            $baseUrl = $this->getBaseUrl($ambiente);
            $client = new RestClient($certManager, $baseUrl);

            $res = $client->request('POST', "/nfse/{$cleanChave}/eventos", [
                'pedidoRegistroEventoXmlGZipB64' => $b64
            ]);

            $c->xmlRetorno = $res['response_body'];

            if ($res['http_code'] === 201 && is_array($res['json'])) {
                $c->success = true;
                $c->cancelada = true;
                $c->protocolo = $preId;
                $c->message = "Cancelamento registrado com sucesso no Emissor Nacional.";
            } else {
                $c->success = false;
                $c->cancelada = false;
                $erros = [];
                $rawErros = [];
                if (is_array($res['json'])) {
                    if (!empty($res['json']['erros']) && is_array($res['json']['erros'])) {
                        $rawErros = $res['json']['erros'];
                    } elseif (!empty($res['json']['Erros']) && is_array($res['json']['Erros'])) {
                        $rawErros = $res['json']['Erros'];
                    }
                }

                if (!empty($rawErros)) {
                    foreach ($rawErros as $err) {
                        if (is_string($err)) {
                            $erros[] = $err;
                            continue;
                        }
                        $cod = $err['codigo'] ?? $err['Codigo'] ?? '';
                        $desc = $err['descricao'] ?? $err['Descricao'] ?? '';
                        $comp = $err['complemento'] ?? $err['Complemento'] ?? '';
                        $prefixo = $cod ? "[$cod] " : "";
                        $sufixo = $comp ? " ($comp)" : "";
                        $linha = trim("{$prefixo}{$desc}{$sufixo}");
                        if (!empty($linha)) {
                            $erros[] = $linha;
                        }
                    }
                }
                $c->message = !empty($erros) ? implode("; ", $erros) : "Falha ao registrar cancelamento (HTTP {$res['http_code']}).";
            }
        } catch (Exception $e) {
            $c->success = false;
            $c->message = "Erro ao cancelar NFS-e via REST: " . $e->getMessage();
        } finally {
            if (isset($certManager)) {
                $certManager->cleanup();
            }
        }

        return $c;
    }

    public function consultarDadosCadastrais(?string $cnpj = null, ?string $im = null): CadastroResult
    {
        $res = new CadastroResult();
        $res->success = true;
        $res->cnpj = $cnpj ?: ($this->config['cnpj'] ?? '');
        $res->inscricaoMunicipal = $im ?: ($this->config['inscricao_municipal'] ?? '');
        $res->message = "No Emissor Público Nacional REST, as validações tributárias são efetuadas pela API de Parâmetros Municipais (/parametrizacao) e pelo Cadastro Nacional de Contribuintes (CNC).";
        return $res;
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

    private function getBaseUrl(string $ambiente): string
    {
        return ($ambiente === 'producao')
            ? 'https://sefin.nfse.gov.br/SefinNacional'
            : 'https://sefin.producaorestrita.nfse.gov.br/API/SefinNacional';
    }
}

