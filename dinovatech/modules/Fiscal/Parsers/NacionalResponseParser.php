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
        if (strpos($responseXml, '<Fault>') !== false || strpos($responseXml, ':Fault>') !== false) {
            $result->success = false;
            $result->status = 'erro';
            $result->message = 'Erro de comunicação SOAP (Fault).';
            if (preg_match('/<faultstring>(.*?)<\/faultstring>/s', $responseXml, $m)) {
                $result->details = trim(strip_tags($m[1]));
            }
            return $result;
        }

        // Verifica erros retornados pelo fisco em <ListaMensagemRetorno>
        $erros = $this->extractMessages($responseXml);
        if (!empty($erros)) {
            $result->erros = $erros;
            $result->details = implode("\n", $erros);
        }

        // Checa sucesso: presença de <CompNfse> ou <Nfse> ou <infNFSe>
        if (strpos($responseXml, '<CompNfse>') !== false || strpos($responseXml, '<Nfse') !== false || strpos($responseXml, '<infNFSe') !== false) {
            $result->success = true;
            $result->status = 'concluido';
            $result->message = 'NFS-e emitida com sucesso no Padrão Nacional.';

            // Extrai número da nota
            if (preg_match('/<nNFSe>(.*?)<\/nNFSe>/', $responseXml, $m) || preg_match('/<nDFSe>(.*?)<\/nDFSe>/', $responseXml, $m)) {
                $result->numeroNota = trim($m[1]);
            }

            // Extrai Chave de Acesso Nacional (50 dígitos)
            if (preg_match('/<infNFSe[^>]*Id="NFS([0-9A-Z]{50})"/i', $responseXml, $m)) {
                $result->chaveNfse = trim($m[1]);
            } elseif (preg_match('/<chNFSe>(.*?)<\/chNFSe>/', $responseXml, $m)) {
                $result->chaveNfse = trim($m[1]);
            }

            // Código de verificação
            if (preg_match('/<cVerifNFSeMun>(.*?)<\/cVerifNFSeMun>/', $responseXml, $m) || preg_match('/<cVerif>(.*?)<\/cVerif>/', $responseXml, $m)) {
                $result->codigoVerificacao = trim($m[1]);
            }
        } else {
            $result->success = false;
            $result->status = 'erro';
            $result->message = !empty($erros) ? 'NFS-e recusada pelo fisco.' : 'Erro ao processar retorno da NFS-e.';
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
        if (preg_match_all('/<MensagemRetorno>(.*?)<\/MensagemRetorno>/s', $xml, $matches)) {
            foreach ($matches[1] as $item) {
                $cod = '';
                $msg = '';
                $corr = '';
                if (preg_match('/<Codigo>(.*?)<\/Codigo>/', $item, $m)) $cod = trim($m[1]);
                if (preg_match('/<Mensagem>(.*?)<\/Mensagem>/', $item, $m)) $msg = trim($m[1]);
                if (preg_match('/<Correcao>(.*?)<\/Correcao>/', $item, $m)) $corr = trim($m[1]);

                $entry = $cod ? "[$cod] $msg" : $msg;
                if ($corr) {
                    $entry .= " (Correção: $corr)";
                }
                if ($entry) {
                    $messages[] = $entry;
                }
            }
        }
        return $messages;
    }
}
