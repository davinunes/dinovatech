<?php
namespace Dinovatech\Modules\Fiscal\Builders;

class ConsultarUrlXmlBuilder
{
    public function build(string $cnpjPrestador, string $imPrestador, string $numeroNota): string
    {
        $cleanCnpj = preg_replace('/\D/', '', $cnpjPrestador);
        $cleanIm = preg_replace('/\D/', '', $imPrestador);
        $cleanNum = preg_replace('/\D/', '', $numeroNota);

        return <<<XML
<ConsultarUrlNfseEnvio xmlns="http://www.sped.fazenda.gov.br/nfse">
    <Prestador>
        <CNPJ>{$cleanCnpj}</CNPJ>
        <IM>{$cleanIm}</IM>
    </Prestador>
    <NumeroNfse>{$cleanNum}</NumeroNfse>
    <Pagina>1</Pagina>
</ConsultarUrlNfseEnvio>
XML;
    }
}
