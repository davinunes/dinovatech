<?php
namespace Dinovatech\Modules\Fiscal\Builders;

class ConsultarDadosCadastraisXmlBuilder
{
    public function build(string $cnpjPrestador, string $imPrestador): string
    {
        $cleanCnpj = preg_replace('/\D/', '', $cnpjPrestador);
        $cleanIm = preg_replace('/\D/', '', $imPrestador);

        return <<<XML
<ConsultarDadosCadastraisEnvio xmlns="http://www.sped.fazenda.gov.br/nfse" xmlns:ns2="http://www.w3.org/2000/09/xmldsig#">
    <Prestador>
        <CNPJ>{$cleanCnpj}</CNPJ>
        <IM>{$cleanIm}</IM>
    </Prestador>
</ConsultarDadosCadastraisEnvio>
XML;
    }
}
