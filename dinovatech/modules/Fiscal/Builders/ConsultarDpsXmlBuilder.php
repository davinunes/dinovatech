<?php
namespace Dinovatech\Modules\Fiscal\Builders;

class ConsultarDpsXmlBuilder
{
    public function build(string $serieDps, int $numeroDps, string $cnpjPrestador, string $imPrestador): string
    {
        $cleanCnpj = preg_replace('/\D/', '', $cnpjPrestador);
        $cleanIm = preg_replace('/\D/', '', $imPrestador);

        return <<<XML
<ConsultarNfseDpsEnvio xmlns="http://www.sped.fazenda.gov.br/nfse">
    <IdentificacaoDps>
        <NumDPS>{$numeroDps}</NumDPS>
        <SerieDPS>{$serieDps}</SerieDPS>
    </IdentificacaoDps>
    <Prestador>
        <CNPJ>{$cleanCnpj}</CNPJ>
        <IM>{$cleanIm}</IM>
    </Prestador>
</ConsultarNfseDpsEnvio>
XML;
    }
}
