<?php
namespace Dinovatech\Modules\Fiscal\Parsers;

use Dinovatech\Modules\Fiscal\DTOs\EmissionResult;
use Dinovatech\Modules\Fiscal\DTOs\QueryResult;
use Dinovatech\Modules\Fiscal\DTOs\CancellationResult;
use Dinovatech\Modules\Fiscal\DTOs\UrlResult;
use Dinovatech\Modules\Fiscal\DTOs\CadastroResult;
use DOMDocument;
use DOMXPath;

class NacionalResponseParser
{
    public function parseEmissao(string $responseXml, string $xmlEnvio, string $dpsId): EmissionResult
    {
        $result = new EmissionResult();
        $result->xmlEnvio = $xmlEnvio;
        $result->xmlRetorno = $responseXml;
        $result->idDps = $dpsId;

        if (empty($responseXml)) {
            $result->success = false;
            $result->status = 'erro';
            $result->message = 'Resposta vazia recebida do servidor da SEFAZ/Nota Control.';
            return $result;
        }

        // Se houver SOAP Fault
        if (stripos($responseXml, 'Fault>') !== false) {
            $result->success = false;
            $result->status = 'erro';
            $result->message = 'Erro de comunicação SOAP (Fault).';
            if (preg_match('/<(?:\w+:)?faultstring\b[^>]*>(.*?)<\/(?:\w+:)?faultstring>/si', $responseXml, $m)) {
                $result->details = trim(strip_tags(htmlspecialchars_decode($m[1])));
            } else {
                $result->details = trim(strip_tags(htmlspecialchars_decode($responseXml)));
            }
            return $result;
        }

        // Verifica erros retornados pelo fisco em <ListaMensagemRetorno> ou similares
        $erros = $this->extractMessages($responseXml);
        if (!empty($erros)) {
            $result->erros = $erros;
            $result->details = implode("\n", $erros);
        }

        // Checa sucesso: presença de <CompNfse> ou <Nfse> ou <infNFSe>
        if (stripos($responseXml, '<CompNfse') !== false || stripos($responseXml, '<Nfse') !== false || stripos($responseXml, '<infNFSe') !== false) {
            $result->success = true;
            $result->status = 'concluido';
            $result->message = 'NFS-e emitida com sucesso no Padrão Nacional.';

            // Extrai número da nota
            if (preg_match('/<(?:\w+:)?nNFSe\b[^>]*>(.*?)<\/(?:\w+:)?nNFSe>/si', $responseXml, $m) || 
                preg_match('/<(?:\w+:)?nDFSe\b[^>]*>(.*?)<\/(?:\w+:)?nDFSe>/si', $responseXml, $m)) {
                $result->numeroNota = trim($m[1]);
            }

            // Extrai Chave de Acesso Nacional (50 dígitos)
            if (preg_match('/Id="NFS([0-9A-Z]{50})"/i', $responseXml, $m) || 
                preg_match('/<(?:\w+:)?chNFSe\b[^>]*>(.*?)<\/(?:\w+:)?chNFSe>/si', $responseXml, $m)) {
                $result->chaveNfse = trim($m[1]);
            }

            // Código de verificação
            if (preg_match('/<(?:\w+:)?cVerifNFSeMun\b[^>]*>(.*?)<\/(?:\w+:)?cVerifNFSeMun>/si', $responseXml, $m) || 
                preg_match('/<(?:\w+:)?cVerif\b[^>]*>(.*?)<\/(?:\w+:)?cVerif>/si', $responseXml, $m)) {
                $result->codigoVerificacao = trim($m[1]);
            }
        } else {
            $result->success = false;
            $result->status = 'erro';
            $result->message = !empty($erros) ? 'NFS-e recusada pelo fisco.' : 'Erro ao processar retorno da NFS-e.';
            if (empty($result->details)) {
                $cleanText = trim(preg_replace('/\s+/', ' ', strip_tags(htmlspecialchars_decode($responseXml))));
                $result->details = !empty($cleanText) ? mb_substr($cleanText, 0, 1000) : 'Servidor da NFS-e não retornou mensagem de erro legível.';
            }
        }

        return $result;
    }

    public function parseConsultaDps(string $responseXml): QueryResult
    {
        $result = new QueryResult();
        $result->xmlRetorno = $responseXml;

        if (empty($responseXml)) {
            $result->success = false;
            $result->message = 'Resposta vazia na consulta da DPS.';
            return $result;
        }

        $erros = $this->extractMessages($responseXml);
        if (!empty($erros)) {
            $result->erros = $erros;
        }

        if (strpos($responseXml, '<CompNfse>') !== false || strpos($responseXml, '<infNFSe') !== false) {
            $result->success = true;
            $result->encontrada = true;
            $result->message = 'NFS-e localizada com sucesso.';

            if (preg_match('/<nNFSe>(.*?)<\/nNFSe>/', $responseXml, $m) || preg_match('/<nDFSe>(.*?)<\/nDFSe>/', $responseXml, $m)) {
                $result->numeroNota = trim($m[1]);
            }

            if (preg_match('/<infNFSe[^>]*Id="NFS([0-9A-Z]{50})"/i', $responseXml, $m)) {
                $result->chaveNfse = trim($m[1]);
            }

            if (preg_match('/<cVerifNFSeMun>(.*?)<\/cVerifNFSeMun>/', $responseXml, $m)) {
                $result->codigoVerificacao = trim($m[1]);
            }
        } else {
            $result->success = true;
            $result->encontrada = false;
            $result->message = !empty($erros) ? implode('; ', $erros) : 'Nota não encontrada para a DPS informada.';
        }

        return $result;
    }

    public function parseConsultarUrl(string $responseXml): UrlResult
    {
        $result = new UrlResult();
        $result->xmlRetorno = $responseXml;

        if (empty($responseXml)) {
            $result->success = false;
            $result->message = 'Resposta vazia na consulta de URLs.';
            return $result;
        }

        if (preg_match('/<UrlVisualizacaoNfse>(.*?)<\/UrlVisualizacaoNfse>/', $responseXml, $m)) {
            $result->urlVisualizacao = htmlspecialchars_decode(trim($m[1]));
            $result->success = true;
        }

        if (preg_match('/<UrlVerificaAutenticidade>(.*?)<\/UrlVerificaAutenticidade>/', $responseXml, $m)) {
            $result->urlVerificacaoAutenticidade = htmlspecialchars_decode(trim($m[1]));
        }

        if (preg_match('/<UrlVisualizacaoNfseNacional>(.*?)<\/UrlVisualizacaoNfseNacional>/', $responseXml, $m)) {
            $result->urlVisualizacaoNacional = htmlspecialchars_decode(trim($m[1]));
            $result->success = true;
        }

        if (!$result->success) {
            $erros = $this->extractMessages($responseXml);
            $result->message = !empty($erros) ? implode('; ', $erros) : 'URLs não encontradas no retorno.';
        }

        return $result;
    }

    public function parseCancelamento(string $responseXml, string $xmlEnvio): CancellationResult
    {
        $result = new CancellationResult();
        $result->xmlEnvio = $xmlEnvio;
        $result->xmlRetorno = $responseXml;

        if (empty($responseXml)) {
            $result->success = false;
            $result->message = 'Resposta vazia no cancelamento de NFS-e.';
            return $result;
        }

        $erros = $this->extractMessages($responseXml);
        if (!empty($erros)) {
            $result->erros = $erros;
        }

        if (strpos($responseXml, '<ListaEvento>') !== false || strpos($responseXml, '<infEvento>') !== false || strpos($responseXml, 'Cancelamento') !== false) {
            $result->success = true;
            $result->message = 'Pedido de cancelamento registrado com sucesso.';

            if (preg_match('/<nDFSe>(.*?)<\/nDFSe>/', $responseXml, $m)) {
                $result->protocoloEvento = trim($m[1]);
            }
        } else {
            $result->success = false;
            $result->message = !empty($erros) ? implode('; ', $erros) : 'Erro ao processar pedido de cancelamento.';
        }

        return $result;
    }

    public function parseConsultaCadastro(string $responseXml): CadastroResult
    {
        $result = new CadastroResult();
        $result->xmlRetorno = $responseXml;

        if (empty($responseXml)) {
            $result->success = false;
            $result->message = 'Resposta vazia na consulta cadastral da SEFAZ-DF.';
            return $result;
        }

        if (stripos($responseXml, 'Fault>') !== false) {
            $result->success = false;
            $result->message = 'Erro SOAP na consulta cadastral da SEFAZ-DF.';
            if (preg_match('/<(?:\w+:)?faultstring\b[^>]*>(.*?)<\/(?:\w+:)?faultstring>/si', $responseXml, $m)) {
                $result->erros[] = trim(strip_tags(htmlspecialchars_decode($m[1])));
            }
            return $result;
        }

        $erros = $this->extractMessages($responseXml);
        if (!empty($erros)) {
            $result->erros = $erros;
        }

        if (stripos($responseXml, '<Cadastro>') !== false || stripos($responseXml, '<Cadastro ') !== false) {
            $result->success = true;
            $result->message = 'Dados cadastrais recuperados com sucesso da SEFAZ-DF.';

            if (preg_match('/<CNPJ\b[^>]*>(.*?)<\/CNPJ>/si', $responseXml, $m)) $result->cnpj = trim($m[1]);
            if (preg_match('/<IM\b[^>]*>(.*?)<\/IM>/si', $responseXml, $m)) $result->im = trim($m[1]);
            if (preg_match('/<StatusCadastro\b[^>]*>(.*?)<\/StatusCadastro>/si', $responseXml, $m)) $result->statusCadastro = trim($m[1]);
            if (preg_match('/<xNome\b[^>]*>(.*?)<\/xNome>/si', $responseXml, $m)) $result->razaoSocial = trim($m[1]);
            if (preg_match('/<xFant\b[^>]*>(.*?)<\/xFant>/si', $responseXml, $m)) $result->nomeFantasia = trim($m[1]);

            // Endereço
            if (preg_match('/<xLgr\b[^>]*>(.*?)<\/xLgr>/si', $responseXml, $m)) $result->logradouro = trim($m[1]);
            if (preg_match('/<xBairro\b[^>]*>(.*?)<\/xBairro>/si', $responseXml, $m)) $result->bairro = trim($m[1]);
            if (preg_match('/<cMun\b[^>]*>(.*?)<\/cMun>/si', $responseXml, $m)) $result->codigoMunicipio = trim($m[1]);
            if (preg_match('/<UF\b[^>]*>(.*?)<\/UF>/si', $responseXml, $m)) $result->uf = trim($m[1]);
            if (preg_match('/<CEP\b[^>]*>(.*?)<\/CEP>/si', $responseXml, $m)) $result->cep = trim($m[1]);

            // Contato
            if (preg_match('/<fone\b[^>]*>(.*?)<\/fone>/si', $responseXml, $m)) $result->telefone = trim($m[1]);
            if (preg_match('/<email\b[^>]*>(.*?)<\/email>/si', $responseXml, $m)) $result->email = trim($m[1]);

            // Flags
            if (preg_match('/<EmiteNfse\b[^>]*>(.*?)<\/EmiteNfse>/si', $responseXml, $m)) $result->emiteNfse = (trim($m[1]) === '1');
            if (preg_match('/<PermiteDescontoCondicionado\b[^>]*>(.*?)<\/PermiteDescontoCondicionado>/si', $responseXml, $m)) $result->permiteDescontoCondicionado = (trim($m[1]) === '1');
            if (preg_match('/<PermiteDescontoIncondicionado\b[^>]*>(.*?)<\/PermiteDescontoIncondicionado>/si', $responseXml, $m)) $result->permiteDescontoIncondicionado = (trim($m[1]) === '1');

            // Simples e MEI
            if (preg_match('/<OptanteSimplesNacional\b[^>]*>(.*?)<\/OptanteSimplesNacional>/si', $responseXml, $m)) {
                $result->optanteSimples = (trim($m[1]) === '1');
            }
            if (preg_match('/<OpcaoSimplesNacional\b[^>]*>.*?<DataInicial\b[^>]*>(.*?)<\/DataInicial>.*?<\/OpcaoSimplesNacional>/si', $responseXml, $m)) {
                $result->dataSimples = trim($m[1]);
            }
            if (preg_match('/<OptanteMei\b[^>]*>(.*?)<\/OptanteMei>/si', $responseXml, $m)) {
                $result->optanteMei = (trim($m[1]) === '1');
            }

            // Tributações permitidas (tribISSQN)
            if (preg_match_all('/<tribISSQN\b[^>]*>(.*?)<\/tribISSQN>/si', $responseXml, $matchesTrib)) {
                foreach ($matchesTrib[1] as $tVal) {
                    $result->tributacoesPermitidas[] = (int)trim($tVal);
                }
            }

            // Atividades
            if (preg_match_all('/<Atividade\b[^>]*>(.*?)<\/Atividade>/si', $responseXml, $matchesAtiv)) {
                $hoje = date('Y-m-d');
                foreach ($matchesAtiv[1] as $itemAtiv) {
                    $cTribMun = '';
                    $xTribMun = '';
                    $pAliq = 0.0;
                    $dtIni = null;
                    $dtFim = null;

                    if (preg_match('/<cTribMun\b[^>]*>(.*?)<\/cTribMun>/si', $itemAtiv, $m)) $cTribMun = trim($m[1]);
                    if (preg_match('/<xTribMun\b[^>]*>(.*?)<\/xTribMun>/si', $itemAtiv, $m)) $xTribMun = trim($m[1]);
                    if (preg_match('/<pAliq\b[^>]*>(.*?)<\/pAliq>/si', $itemAtiv, $m)) $pAliq = (float)trim($m[1]);
                    if (preg_match('/<DataInicial\b[^>]*>(.*?)<\/DataInicial>/si', $itemAtiv, $m)) $dtIni = trim($m[1]);
                    if (preg_match('/<DataFinal\b[^>]*>(.*?)<\/DataFinal>/si', $itemAtiv, $m)) $dtFim = trim($m[1]);

                    $ativa = empty($dtFim) || ($dtFim >= $hoje);

                    $ativObj = [
                        'codigo' => $cTribMun,
                        'descricao' => $xTribMun,
                        'aliquota' => $pAliq,
                        'aliquota_formatada' => number_format($pAliq, 2, ',', '.') . '%',
                        'data_inicial' => $dtIni,
                        'data_final' => $dtFim,
                        'ativa' => $ativa
                    ];

                    $result->atividades[] = $ativObj;
                    if ($ativa) {
                        $result->atividadesVigentes[] = $ativObj;
                    }
                }
            }
        } else {
            $result->success = false;
            $result->message = !empty($erros) ? implode('; ', $erros) : 'Dados cadastrais não localizados no retorno da SEFAZ-DF.';
        }

        return $result;
    }

    private function extractMessages(string $xml): array
    {
        $messages = [];

        // 1. Padrão ABRASF / Nota Control: <MensagemRetorno> com ou sem namespace
        if (preg_match_all('/<(?:\w+:)?MensagemRetorno\b[^>]*>(.*?)<\/(?:\w+:)?MensagemRetorno>/si', $xml, $matches)) {
            foreach ($matches[1] as $item) {
                $cod = '';
                $msg = '';
                $corr = '';
                if (preg_match('/<(?:\w+:)?Codigo\b[^>]*>(.*?)<\/(?:\w+:)?Codigo>/si', $item, $m)) $cod = trim($m[1]);
                if (preg_match('/<(?:\w+:)?Mensagem\b[^>]*>(.*?)<\/(?:\w+:)?Mensagem>/si', $item, $m)) $msg = trim($m[1]);
                if (preg_match('/<(?:\w+:)?Correcao\b[^>]*>(.*?)<\/(?:\w+:)?Correcao>/si', $item, $m)) $corr = trim($m[1]);

                $entry = $cod ? "[$cod] $msg" : $msg;
                if ($corr) {
                    $entry .= " (Correção: $corr)";
                }
                if ($entry) {
                    $messages[] = $entry;
                }
            }
        }

        // 2. Padrão SPED Nacional: <cStat> / <xMotivo>
        if (empty($messages)) {
            if (preg_match('/<(?:\w+:)?cStat\b[^>]*>(.*?)<\/(?:\w+:)?cStat>/si', $xml, $mStat)) {
                $cStat = trim($mStat[1]);
                $xMotivo = '';
                if (preg_match('/<(?:\w+:)?xMotivo\b[^>]*>(.*?)<\/(?:\w+:)?xMotivo>/si', $xml, $mMot)) {
                    $xMotivo = trim($mMot[1]);
                }
                if ($cStat || $xMotivo) {
                    $messages[] = "[$cStat] $xMotivo";
                }
            }
        }

        // 3. Padrão <erro><descricao> ou <erro><mensagem>
        if (empty($messages)) {
            if (preg_match_all('/<(?:\w+:)?erro\b[^>]*>(.*?)<\/(?:\w+:)?erro>/si', $xml, $matches)) {
                foreach ($matches[1] as $item) {
                    $txt = trim(strip_tags($item));
                    if ($txt) $messages[] = $txt;
                }
            }
        }

        return $messages;
    }
}
