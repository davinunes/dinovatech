<?php
namespace Dinovatech\Modules\Fiscal\Builders;

use Dinovatech\Modules\Fiscal\Formatters\DpsIdGenerator;

class CancelarNfseXmlBuilder
{
    public function build(string $chaveNfse, string $cnpjAutor, int $codigoMotivo, string $justificativa, string $ambiente = 'homologacao'): array
    {
        $tpAmb = ($ambiente === 'producao') ? '1' : '2';
        $verAplic = 'Dinovatech_1.0';
        $dhEvento = date('Y-m-d\TH:i:sP');
        $cleanChave = preg_replace('/\D/', '', $chaveNfse);
        $cleanCnpj = preg_replace('/\D/', '', $cnpjAutor);

        $preId = DpsIdGenerator::generatePedidoEventoId($cleanChave, '101103');

        $xMotivo = htmlspecialchars(trim($justificativa), ENT_XML1, 'UTF-8');
        if (strlen($xMotivo) < 15) {
            $xMotivo = str_pad($xMotivo, 15, ' ', STR_PAD_RIGHT);
        }

        $xml = <<<XML
<CancelarNfseEnvio xmlns="http://www.sped.fazenda.gov.br/nfse">
    <pedRegEvento versao="1.01">
        <infPedReg Id="{$preId}">
            <tpAmb>{$tpAmb}</tpAmb>
            <verAplic>{$verAplic}</verAplic>
            <dhEvento>{$dhEvento}</dhEvento>
            <CNPJAutor>{$cleanCnpj}</CNPJAutor>
            <chNFSe>{$cleanChave}</chNFSe>
            <e101103>
                <xDesc>Solicitação de Análise Fiscal para Cancelamento de NFS-e</xDesc>
                <cMotivo>{$codigoMotivo}</cMotivo>
                <xMotivo>{$xMotivo}</xMotivo>
            </e101103>
        </infPedReg>
    </pedRegEvento>
</CancelarNfseEnvio>
XML;

        return [
            'xml' => trim($xml),
            'preId' => $preId
        ];
    }
}
