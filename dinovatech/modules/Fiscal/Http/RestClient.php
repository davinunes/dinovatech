<?php
namespace Dinovatech\Modules\Fiscal\Http;

use Dinovatech\Modules\Fiscal\Security\CertificateManager;
use Exception;

class RestClient
{
    private CertificateManager $certManager;
    private string $baseUrl;
    private int $timeout;

    public function __construct(CertificateManager $certManager, string $baseUrl, int $timeout = 60)
    {
        $this->certManager = $certManager;
        $this->baseUrl = rtrim($baseUrl, '/');
        $this->timeout = $timeout;
    }

    /**
     * Executa requisição HTTP REST com autenticação mTLS (Certificado Cliente A1 ICP-Brasil)
     *
     * @param string $method GET, POST, HEAD, PUT, DELETE
     * @param string $path Caminho do endpoint (ex: '/nfse', '/dps/DPS...')
     * @param array|null $body Payload em array a ser convertido em JSON (ou nulo)
     * @param array $headers Cabeçalhos HTTP adicionais
     * @return array ['http_code' => int, 'response_body' => string, 'json' => ?array, 'curl_error' => string]
     */
    public function request(string $method, string $path, ?array $body = null, array $headers = []): array
    {
        $url = $this->baseUrl . '/' . ltrim($path, '/');
        $tlsFiles = $this->certManager->getTlsFiles();

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, $this->timeout);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, strtoupper($method));

        // Autenticação mTLS
        curl_setopt($ch, CURLOPT_SSLCERT, $tlsFiles['cert']);
        curl_setopt($ch, CURLOPT_SSLKEY, $tlsFiles['key']);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);

        $defaultHeaders = [
            'Accept: application/json',
            'User-Agent: Dinovatech-NFSe-Client/1.0'
        ];

        if ($body !== null) {
            $jsonPayload = json_encode($body, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $jsonPayload);
            $defaultHeaders[] = 'Content-Type: application/json; charset=utf-8';
            $defaultHeaders[] = 'Content-Length: ' . strlen($jsonPayload);
        } elseif (in_array(strtoupper($method), ['POST', 'PUT'])) {
            $defaultHeaders[] = 'Content-Length: 0';
        }

        if (strtoupper($method) === 'HEAD') {
            curl_setopt($ch, CURLOPT_NOBODY, true);
        }

        $allHeaders = array_merge($defaultHeaders, $headers);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $allHeaders);

        $responseBody = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);

        $jsonParsed = null;
        if (!empty($responseBody)) {
            $jsonParsed = json_decode($responseBody, true);
        }

        return [
            'http_code' => (int)$httpCode,
            'response_body' => $responseBody ?: '',
            'json' => $jsonParsed,
            'curl_error' => $curlError ?: ''
        ];
    }
}

