<?php
namespace Dinovatech\Modules\Fiscal\Builders;

class ConsultarUrlXmlBuilder
{
    public function build(string $cnpjPrestador, string $imPrestador, ?string $numeroNota = null, ?string $serieDps = null, ?int $numeroDps = null): string
    {
        $cleanCnpj = preg_replace('/\D/', '', $cnpjPrestador);
        $cleanIm = preg_replace('/\D/', '', $imPrestador);

        $identificadorXml = '';
        if (!empty($numeroNota) && $numeroNota !== '0') {
            $cleanNum = preg_replace('/\D/', '', $numeroNota);
            $identificadorXml = "<NumeroNfse>{$cleanNum}</NumeroNfse>";
        } elseif (!empty($numeroDps)) {
            $identificadorXml = "<IdentificacaoDps>
                <NumDPS>{$numeroDps}</NumDPS>
                <SerieDPS>{$serieDps}</SerieDPS>
            </IdentificacaoDps>";
        } else {
            $identificadorXml = "<NumeroNfse>0</NumeroNfse>";
        }

        return <<<XML
<ConsultarUrlNfseEnvio xmlns="http://www.sped.fazenda.gov.br/nfse">
    <Prestador>
        <CNPJ>{$cleanCnpj}</CNPJ>
        <IM>{$cleanIm}</IM>
    </Prestador>
    {$identificadorXml}
    <Pagina>1</Pagina>
</ConsultarUrlNfseEnvio>
XML;
    }
}
