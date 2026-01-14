function buildConsultarUrlNfseXml($input)
{
$cnpj = $input['cnpj'] ?? '';
$im = $input['im'] ?? '';
$numero = $input['numero_nota'] ?? '';

// Structure based on User's Working Example
// <Pedido>
    <Prestador>...</Prestador>
    <NumeroNfse>...</NumeroNfse>
    <Pagina>1</Pagina>
</Pedido>

$pedidoContent = "<Pedido>";
    $pedidoContent .= "<Prestador>";
        $pedidoContent .= "<CpfCnpj>
            <Cnpj>$cnpj</Cnpj>
        </CpfCnpj>";
        $pedidoContent .= "<InscricaoMunicipal>$im</InscricaoMunicipal>";
        $pedidoContent .= "</Prestador>";
    $pedidoContent .= "<NumeroNfse>$numero</NumeroNfse>";
    $pedidoContent .= "<Pagina>1</Pagina>";
    $pedidoContent .= "</Pedido>";

$rootId = "ConsultarUrlNfseEnvio";

$rootXml = '<ConsultarUrlNfseEnvio xmlns="http://www.abrasf.org.br/nfse.xsd" Id="' . $rootId . '">' . $pedidoContent . '
</ConsultarUrlNfseEnvio>';

return ['root' => $rootXml, 'id' => $rootId];
}