<?php
namespace Dinovatech\Modules\Fiscal\Builders;

class ConsultarNfseDpsXmlBuilder
{
    public function build(string $cnpjPrestador, string $imPrestador, int $numeroDps, string $serieDps): string
    {
        $cleanCnpj = preg_replace('/\D/', '', $cnpjPrestador);
        $cleanIm = preg_replace('/\D/', '', $imPrestador);

        return <<<XML
<ConsultarNfseDpsEnvio xmlns="http://www.sped.fazenda.gov.br/nfse">
    <Prestador>
        <CNPJ>{$cleanCnpj}</CNPJ>
        <IM>{$cleanIm}</IM>
    </Prestador>
    <IdentificacaoDps>
        <NumDPS>{$numeroDps}</NumDPS>
        <SerieDPS>{$serieDps}</SerieDPS>
    </IdentificacaoDps>
</ConsultarNfseDpsEnvio>
XML;
    }
}
