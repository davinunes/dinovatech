<?php
namespace Dinovatech\Modules\Fiscal\Builders;

class ConsultarDpsDisponivelXmlBuilder
{
    public function build(string $cnpjPrestador, string $imPrestador, int $pagina = 1, ?string $serieDps = null, ?int $numeroDps = null): string
    {
        $cleanCnpj = preg_replace('/\D/', '', $cnpjPrestador);
        $cleanIm = preg_replace('/\D/', '', $imPrestador);

        $identificacaoXml = '';
        if (!empty($numeroDps)) {
            $serieStr = htmlspecialchars($serieDps ?: '1', ENT_XML1, 'UTF-8');
            $identificacaoXml = "<IdentificacaoDps>
        <NumDPS>{$numeroDps}</NumDPS>
        <SerieDPS>{$serieStr}</SerieDPS>
    </IdentificacaoDps>";
        }

        return <<<XML
<ConsultarDpsDisponivelEnvio xmlns="http://www.sped.fazenda.gov.br/nfse">
    <Prestador>
        <CNPJ>{$cleanCnpj}</CNPJ>
        <IM>{$cleanIm}</IM>
    </Prestador>
    {$identificacaoXml}
    <Pagina>{$pagina}</Pagina>
</ConsultarDpsDisponivelEnvio>
XML;
    }
}
