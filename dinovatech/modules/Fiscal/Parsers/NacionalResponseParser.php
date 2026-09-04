<?php
namespace Dinovatech\Modules\Fiscal\Parsers;

use Dinovatech\Modules\Fiscal\DTOs\EmissionResult;
use Dinovatech\Modules\Fiscal\DTOs\QueryResult;
use Dinovatech\Modules\Fiscal\DTOs\CancellationResult;
use Dinovatech\Modules\Fiscal\DTOs\UrlResult;
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
            if (preg_match('/<([a-zA-Z0-9_\-]+:)?faultstring\b[^>]*>(.*?)<\/([a-zA-Z0-9_\-]+:)?faultstring>/si', $responseXml, $m)) {
                $result->details = trim(strip_tags(htmlspecialchars_decode($m[2])));
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
            if (preg_match('/<([a-zA-Z0-9_\-]+:)?nNFSe\b[^>]*>(.*?)<\/([a-zA-Z0-9_\-]+:)?nNFSe>/si', $responseXml, $m) || 
                preg_match('/<([a-zA-Z0-9_\-]+:)?nDFSe\b[^>]*>(.*?)<\/([a-zA-Z0-9_\-]+:)?nDFSe>/si', $responseXml, $m)) {
                $result->numeroNota = trim($m[2]);
            }

            // Extrai Chave de Acesso Nacional (50 dígitos)
            if (preg_match('/Id="NFS([0-9A-Z]{50})"/i', $responseXml, $m) || 
                preg_match('/<([a-zA-Z0-9_\-]+:)?chNFSe\b[^>]*>(.*?)<\/([a-zA-Z0-9_\-]+:)?chNFSe>/si', $responseXml, $m)) {
                $result->chaveNfse = trim($m[1] ?? $m[2]);
            }

            // Código de verificação
            if (preg_match('/<([a-zA-Z0-9_\-]+:)?cVerifNFSeMun\b[^>]*>(.*?)<\/([a-zA-Z0-9_\-]+:)?cVerifNFSeMun>/si', $responseXml, $m) || 
                preg_match('/<([a-zA-Z0-9_\-]+:)?cVerif\b[^>]*>(.*?)<\/([a-zA-Z0-9_\-]+:)?cVerif>/si', $responseXml, $m)) {
                $result->codigoVerificacao = trim($m[2]);
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

    private function extractMessages(string $xml): array
    {
        $messages = [];

        // 1. Padrão ABRASF / Nota Control: <MensagemRetorno> com ou sem namespace
        if (preg_match_all('/<([a-zA-Z0-9_\-]+:)?MensagemRetorno\b[^>]*>(.*?)<\/([a-zA-Z0-9_\-]+:)?MensagemRetorno>/si', $xml, $matches)) {
            foreach ($matches[2] as $item) {
                $cod = '';
                $msg = '';
                $corr = '';
                if (preg_match('/<([a-zA-Z0-9_\-]+:)?Codigo\b[^>]*>(.*?)<\/([a-zA-Z0-9_\-]+:)?Codigo>/si', $item, $m)) $cod = trim($m[2]);
                if (preg_match('/<([a-zA-Z0-9_\-]+:)?Mensagem\b[^>]*>(.*?)<\/([a-zA-Z0-9_\-]+:)?Mensagem>/si', $item, $m)) $msg = trim($m[2]);
                if (preg_match('/<([a-zA-Z0-9_\-]+:)?Correcao\b[^>]*>(.*?)<\/([a-zA-Z0-9_\-]+:)?Correcao>/si', $item, $m)) $corr = trim($m[2]);

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
            if (preg_match('/<([a-zA-Z0-9_\-]+:)?cStat\b[^>]*>(.*?)<\/([a-zA-Z0-9_\-]+:)?cStat>/si', $xml, $mStat)) {
                $cStat = trim($mStat[2]);
                $xMotivo = '';
                if (preg_match('/<([a-zA-Z0-9_\-]+:)?xMotivo\b[^>]*>(.*?)<\/([a-zA-Z0-9_\-]+:)?xMotivo>/si', $xml, $mMot)) {
                    $xMotivo = trim($mMot[2]);
                }
                if ($cStat || $xMotivo) {
                    $messages[] = "[$cStat] $xMotivo";
                }
            }
        }

        // 3. Padrão <erro><descricao> ou <erro><mensagem>
        if (empty($messages)) {
            if (preg_match_all('/<([a-zA-Z0-9_\-]+:)?erro\b[^>]*>(.*?)<\/([a-zA-Z0-9_\-]+:)?erro>/si', $xml, $matches)) {
                foreach ($matches[2] as $item) {
                    $txt = trim(strip_tags($item));
                    if ($txt) $messages[] = $txt;
                }
            }
        }

        return $messages;
    }
}
