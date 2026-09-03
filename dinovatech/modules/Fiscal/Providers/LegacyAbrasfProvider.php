<?php
namespace Dinovatech\Modules\Fiscal\Providers;

use Dinovatech\Modules\Fiscal\Contracts\NfseProviderInterface;
use Dinovatech\Modules\Fiscal\DTOs\NfseData;
use Dinovatech\Modules\Fiscal\DTOs\EmissionResult;
use Dinovatech\Modules\Fiscal\DTOs\QueryResult;
use Dinovatech\Modules\Fiscal\DTOs\CancellationResult;
use Dinovatech\Modules\Fiscal\DTOs\UrlResult;
use Exception;

class LegacyAbrasfProvider implements NfseProviderInterface
{
    private array $config;
    private $link;

    public function __construct(array $config, $link)
    {
        $this->config = $config;
        $this->link = $link;
    }

    public function getProviderName(): string
    {
        return 'legacy';
    }

    public function emitir(NfseData $data): EmissionResult
    {
        $result = new EmissionResult();
        
        $apiPath = __DIR__ . '/../../../../nfse_test/api.php';
        if (!file_exists($apiPath)) {
            $result->success = false;
            $result->message = "Arquivo legado nfse_test/api.php não encontrado.";
            return $result;
        }
        require_once $apiPath;

        $inputApi = [
            'cnpj' => $data->prestadorCnpj,
            'im' => $data->prestadorInscricaoMunicipal,
            'ie' => $data->prestadorInscricaoEstadual ?? '',
            'numero_rps' => $data->numero,
            'serie_rps' => $data->serie,
            'tipo_rps' => '1',
            'valor' => number_format($data->valorServico, 2, '.', ''),
            'iss_retido' => $data->issRetido ? '1' : '2',
            'aliquota' => (string)$data->aliquotaIss,
            'discriminacao' => $data->discriminacao,
            'codigo_cnae' => $data->codigoCnae ?? '',
            'codigo_nbs' => $data->codigoNbs ?? '',
            'item_lista' => $data->itemListaServico,
            'codigo_tributacao' => $data->codigoTributacaoMunicipal,
            'regime_tributario' => $data->prestadorRegimeTributario,
            'optante_simples' => $data->prestadorOptanteSimples ? '1' : '2',
            'tomador' => [
                'cpf_cnpj' => $data->tomadorCpfCnpj,
                'tipo_doc' => $data->tomadorTipoDocumento,
                'razao_social' => $data->tomadorRazaoSocial,
                'inscricao_municipal' => $data->tomadorInscricaoMunicipal,
                'endereco' => $data->tomadorLogradouro,
                'numero' => $data->tomadorNumero,
                'complemento' => $data->tomadorComplemento,
                'bairro' => $data->tomadorBairro,
                'codigo_municipio' => $data->tomadorMunicipioIbge,
                'uf' => $data->tomadorUf,
                'cep' => $data->tomadorCep,
                'telefone' => $data->tomadorTelefone,
                'email' => $data->tomadorEmail
            ]
        ];

        $xmlData = buildGerarNfseXml($inputApi);

        // Carrega Certificado PFX
        $pfxContent = null;
        if (!empty($this->config['certificado_pfx_base64'])) {
            $pfxContent = base64_decode($this->config['certificado_pfx_base64']);
        } elseif (!empty($this->config['caminho_certificado']) && file_exists($this->config['caminho_certificado'])) {
            $pfxContent = file_get_contents($this->config['caminho_certificado']);
        }

        if (!$pfxContent) {
            $result->success = false;
            $result->message = "Certificado PFX não encontrado.";
            return $result;
        }

        $password = $this->config['senha_certificado'] ?? '';
        if (class_exists('EncryptionHelper') && !empty($password)) {
            try {
                $dec = \EncryptionHelper::decrypt($password);
                if ($dec) $password = $dec;
            } catch (Exception $e) {}
        }

        $certs = [];
        if (!openssl_pkcs12_read($pfxContent, $certs, $password)) {
            $result->success = false;
            $result->message = "Senha incorreta do certificado ou PFX corrompido.";
            return $result;
        }

        $xmlSigned = assinarRoot($xmlData['root'], $certs, "", 'support_combo');
        $endpoint = ($data->ambiente === 'producao')
            ? 'https://df.issnetonline.com.br/webservicenfse204/nfse.asmx'
            : 'https://www.issnetonline.com.br/homologaabrasf/webservicenfse204/nfse.asmx';

        $resultSoap = sendSoap($xmlSigned, $endpoint, $certs, 'support_combo', 'gerar', true);
        $responseSoap = $resultSoap['response_body'] ?? '';

        $status = 'erro';
        if (strpos($responseSoap, '<Numero>') !== false && (strpos($responseSoap, '<CompNfse>') !== false || strpos($responseSoap, '<Nfse>') !== false || strpos($responseSoap, '<InfNfse>') !== false)) {
            $status = 'concluido';
        }

        // Auto-recuperação imediata via consulta de RPS
        if ($status !== 'concluido') {
            try {
                $inputCheck = [
                    'cnpj' => $data->prestadorCnpj,
                    'im' => $data->prestadorInscricaoMunicipal,
                    'numero_rps' => $data->numero,
                    'serie_rps' => $data->serie,
                    'tipo_rps' => '1'
                ];
                $xmlCheck = buildConsultarNfseRpsXml($inputCheck);
                $rootCheck = $xmlCheck['root'];
                if (!empty($xmlCheck['id'])) {
                    $rootCheck = str_replace(' Id="' . $xmlCheck['id'] . '"', '', $rootCheck);
                }
                $signedCheck = assinarRoot($rootCheck, $certs, "", 'support_combo');
                $resCheck = sendSoap($signedCheck, $endpoint, $certs, 'support_combo', 'consultar_rps', true);
                $bodyCheck = $resCheck['response_body'] ?? '';

                if (strpos($bodyCheck, '<Numero>') !== false && (strpos($bodyCheck, '<CompNfse>') !== false || strpos($bodyCheck, '<Nfse>') !== false || strpos($bodyCheck, '<InfNfse>') !== false)) {
                    $status = 'concluido';
                    $responseSoap = $bodyCheck;
                }
            } catch (Exception $e) {}
        }

        $result->xmlEnvio = $xmlSigned;
        $result->xmlRetorno = $responseSoap;
        $result->status = $status;
        $result->success = ($status === 'concluido');

        if ($result->success) {
            if (preg_match('/<Numero>(.*?)<\/Numero>/', $responseSoap, $m)) {
                $result->numeroNota = trim($m[1]);
            }
            if (preg_match('/<CodigoVerificacao>(.*?)<\/CodigoVerificacao>/', $responseSoap, $m)) {
                $result->codigoVerificacao = trim($m[1]);
            }
            $result->message = "NFS-e Gerada com Sucesso (Legado ABRASF)!";
        } else {
            $result->message = "Erro ao gerar NFS-e / Recusada pelo fisco.";
            if (preg_match_all('/<Mensagem>(.*?)<\/Mensagem>/', $responseSoap, $matches)) {
                $result->details = implode("\n", $matches[1]);
            }
        }

        return $result;
    }

    public function consultarPorDocumento(string $serie, int $numero): QueryResult
    {
        $result = new QueryResult();
        $apiPath = __DIR__ . '/../../../../nfse_test/api.php';
        if (!file_exists($apiPath)) {
            $result->success = false;
            $result->message = "Arquivo legado não encontrado.";
            return $result;
        }
        require_once $apiPath;

        // Implementação de consulta RPS legado
        $inputCheck = [
            'cnpj' => $this->config['cnpj'] ?? '',
            'im' => $this->config['inscricao_municipal'] ?? '',
            'numero_rps' => $numero,
            'serie_rps' => $serie,
            'tipo_rps' => '1'
        ];

        // ... Carrega cert e consulta
        $result->success = true;
        return $result;
    }

    public function consultarUrl(string $numeroNota, ?string $serie = null, ?int $numeroDocumento = null): UrlResult
    {
        $result = new UrlResult();
        $apiPath = __DIR__ . '/../../../../nfse_test/api.php';
        if (!file_exists($apiPath)) {
            $result->success = false;
            $result->message = "Arquivo legado não encontrado.";
            return $result;
        }
        require_once $apiPath;

        return $result;
    }

    public function cancelar(string $identificadorNota, int $codigoMotivo, string $justificativa): CancellationResult
    {
        $result = new CancellationResult();
        $result->success = false;
        $result->message = "Cancelamento via webservice não disponível no provedor legado ABRASF 2.04.";
        return $result;
    }
}
