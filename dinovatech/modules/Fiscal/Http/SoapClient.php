<?php
namespace Dinovatech\Modules\Fiscal\Http;

use Dinovatech\Modules\Fiscal\Security\CertificateManager;
use Exception;

class SoapClient
{
    private CertificateManager $certManager;
    private string $endpoint;
    private int $timeout;

    public function __construct(CertificateManager $certManager, string $endpoint, int $timeout = 60)
    {
        $this->certManager = $certManager;
        $this->endpoint = $endpoint;
        $this->timeout = $timeout;
    }

    /**
     * Executa chamada SOAP para uma operação específica do Padrão Nacional.
     * 
     * @param string $methodName Ex: 'GerarNfse', 'ConsultarNfseDps', 'ConsultarUrlNfse', 'CancelarNfse'
     * @param string $dadosXml XML correspondente ao payload da operação
     * @param string $versao Versão do cabeçalho ('1.00' ou '1.01')
     * @return array ['http_code' => int, 'response_body' => string, 'curl_error' => string]
     */
    public function call(string $methodName, string $dadosXml, string $versao = '1.01'): array
    {
        $cabecalhoXml = "<cabecalho versao=\"{$versao}\" xmlns=\"http://www.sped.fazenda.gov.br/nfse\"><versaoDados>{$versao}</versaoDados></cabecalho>";

        // Prepara os parâmetros do Envelope (escapados conforme o WSDL wrapped)
        $cabecEscaped = htmlspecialchars($cabecalhoXml, ENT_XML1, 'UTF-8');
        $dadosEscaped = htmlspecialchars($dadosXml, ENT_XML1, 'UTF-8');

        $soapEnvelope = <<<XML
<soap:Envelope xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xmlns:xsd="http://www.w3.org/2001/XMLSchema" xmlns:soap="http://schemas.xmlsoap.org/soap/envelope/">
  <soap:Body>
    <{$methodName} xmlns="http://www.sped.fazenda.gov.br/nfse">
      <nfseCabecMsg>{$cabecEscaped}</nfseCabecMsg>
      <nfseDadosMsg>{$dadosEscaped}</nfseDadosMsg>
    </{$methodName}>
  </soap:Body>
</soap:Envelope>
XML;

        $soapAction = "http://www.sped.fazenda.gov.br/nfse/{$methodName}";

        $tlsFiles = $this->certManager->getTlsFiles();

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $this->endpoint);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $soapEnvelope);
        curl_setopt($ch, CURLOPT_TIMEOUT, $this->timeout);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: text/xml; charset=utf-8',
            'SOAPAction: "' . $soapAction . '"',
            'Content-Length: ' . strlen($soapEnvelope),
            'Accept: text/xml, application/xml, */*'
        ]);

        // Autenticação por Certificado Digital de Cliente (mTLS)
        curl_setopt($ch, CURLOPT_SSLCERT, $tlsFiles['cert']);
        curl_setopt($ch, CURLOPT_SSLKEY, $tlsFiles['key']);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);

        $responseBody = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);

        curl_close($ch);

        // Desembrulha respostas XML se vierem com entidades HTML no retorno
        if ($responseBody && strpos($responseBody, '&lt;') !== false && strpos($responseBody, '&gt;') !== false) {
            $unwrapped = htmlspecialchars_decode($responseBody);
            // Se virar XML válido, adota o corpo desembrulhado
            if (strpos($unwrapped, '<') !== false) {
                $responseBody = $unwrapped;
            }
        }

        return [
            'http_code' => $httpCode,
            'response_body' => $responseBody ?: '',
            'curl_error' => $curlError,
            'request_envelope' => $soapEnvelope
        ];
    }
}
