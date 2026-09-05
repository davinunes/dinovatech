<?php
namespace Dinovatech\Modules\Fiscal\Services;

use Dinovatech\Modules\Fiscal\Contracts\NfseProviderInterface;
use Dinovatech\Modules\Fiscal\Providers\LegacyAbrasfProvider;
use Dinovatech\Modules\Fiscal\Providers\NacionalProvider;
use Dinovatech\Modules\Fiscal\DTOs\NfseData;
use Dinovatech\Modules\Fiscal\DTOs\EmissionResult;
use Dinovatech\Modules\Fiscal\DTOs\QueryResult;
use Dinovatech\Modules\Fiscal\DTOs\CancellationResult;
use Dinovatech\Modules\Fiscal\DTOs\UrlResult;
use AppHelper;
use Exception;

class NfseService
{
    private $link;
    private array $config;
    private NfseProviderInterface $provider;

    public function __construct($link)
    {
        $this->link = $link;
        $this->loadConfig();
        $this->provider = $this->resolveProvider();
    }

    private function loadConfig(): void
    {
        $res = DBExecute($this->link, "SELECT * FROM ConfiguracoesEmissor LIMIT 1");
        $row = $res ? mysqli_fetch_assoc($res) : null;
        if (!$row) {
            throw new Exception("Configurações do emissor fiscal não localizadas no banco de dados.");
        }
        $this->config = $row;
    }

    private function resolveProvider(): NfseProviderInterface
    {
        $configuredProvider = $this->config['nfse_provider'] ?? 'nacional';

        if ($configuredProvider === 'nacional') {
            return new NacionalProvider($this->config, $this->link);
        }

        return new LegacyAbrasfProvider($this->config, $this->link);
    }

    public function getActiveProvider(): NfseProviderInterface
    {
        return $this->provider;
    }

    /**
     * Realiza a emissão de NFS-e para uma fatura existente
     */
    public function emitirPorFatura(int $idFatura, ?int $idUsuarioResponsavel = null): EmissionResult
    {
        // 1. Calcula dados fiscais consolidados via AppHelper
        $calcData = AppHelper::calculateNfseData($this->link, $idFatura);
        if (!$calcData['success']) {
            $err = new EmissionResult();
            $err->success = false;
            $err->message = $calcData['message'];
            return $err;
        }

        if (!empty($calcData['validation_errors'])) {
            $err = new EmissionResult();
            $err->success = false;
            $err->message = "Erro de validação cadastral: " . implode(", ", $calcData['validation_errors']);
            return $err;
        }

        $ambiente = $calcData['ambiente'];
        $isNacional = ($this->provider->getProviderName() === 'nacional');

        // 2. Determina a série e a numeração do documento provisório (DPS ou RPS)
        if ($isNacional) {
            $serie = $this->config['serie_dps'] ?? '1';
            $nextNum = ($ambiente === 'producao')
                ? ((int)($this->config['ultimo_dps_producao'] ?? 0)) + 1
                : ((int)($this->config['ultimo_dps_homologacao'] ?? 0)) + 1;
        } else {
            $serie = $this->config['serie_rps'] ?? '8';
            $nextNum = ($ambiente === 'producao')
                ? ((int)($this->config['ultimo_rps_producao'] ?? 0)) + 1
                : ((int)($this->config['ultimo_rps_homologacao'] ?? 0)) + 1;
        }

        $taxSettings = $calcData['tax_settings'];
        $tomador = $calcData['tomador'];

        // 3. Monta o DTO NfseData
        $dto = new NfseData();
        $dto->idFatura = $idFatura;
        $dto->ambiente = $ambiente;
        $dto->serie = (string)$serie;
        $dto->numero = $nextNum;
        $dto->dataCompetencia = date('Y-m-d');
        $dto->valorServico = (float)$calcData['total_servicos'];
        $dto->aliquotaIss = (float)($taxSettings['aliquota_iss'] ?: '0.00');
        $dto->issRetido = ($taxSettings['iss_retido'] == '1');

        $dto->prestadorCnpj = $this->config['cnpj'];
        $dto->prestadorInscricaoMunicipal = $this->config['inscricao_municipal'];
        $dto->prestadorInscricaoEstadual = $this->config['inscricao_estadual'] ?? null;
        $dto->prestadorRazaoSocial = $this->config['razao_social'];
        $dto->prestadorNomeFantasia = $this->config['nome_fantasia'] ?? $this->config['razao_social'];
        $dto->prestadorRegimeTributario = $this->config['regime_tributario'] ?? 'simples';
        $dto->prestadorOptanteSimples = ($this->config['optante_simples'] == '1');
        $dto->prestadorMunicipioIbge = $this->config['codigo_municipio'] ?: '5300108';

        $dto->tomadorCpfCnpj = $tomador['cpf_cnpj'] ?? null;
        $dto->tomadorTipoDocumento = $tomador['tipo_doc'] ?? null;
        $dto->tomadorInscricaoMunicipal = $tomador['inscricao_municipal'] ?? null;
        $dto->tomadorRazaoSocial = $tomador['razao_social'] ?? '';
        $dto->tomadorEmail = $tomador['email'] ?? null;
        $dto->tomadorTelefone = $tomador['telefone'] ?? null;
        $dto->tomadorLogradouro = $tomador['endereco'] ?? null;
        $dto->tomadorNumero = $tomador['numero'] ?? null;
        $dto->tomadorComplemento = $tomador['complemento'] ?? null;
        $dto->tomadorBairro = $tomador['bairro'] ?? null;
        $dto->tomadorMunicipioIbge = $tomador['codigo_municipio'] ?? '5300108';
        $dto->tomadorUf = $tomador['uf'] ?? 'DF';
        $dto->tomadorCep = $tomador['cep'] ?? null;

        $dto->discriminacao = $calcData['discriminacao'];
        $dto->itemListaServico = $taxSettings['item_lista_servico'] ?? '01.07';

        // Determina o Código de Tributação Nacional (cTribNac de 6 dígitos)
        if (!empty($taxSettings['codigo_tributacao_nacional'])) {
            $dto->codigoTributacaoNacional = str_pad(preg_replace('/\D/', '', $taxSettings['codigo_tributacao_nacional']), 6, '0', STR_PAD_RIGHT);
        } else {
            // Fallback inteligente baseado no Item LC 116 (ex: '01.07' -> '010701')
            $itemDigits = preg_replace('/\D/', '', $taxSettings['item_lista_servico'] ?? '0107');
            $dto->codigoTributacaoNacional = str_pad($itemDigits, 4, '0', STR_PAD_LEFT) . '01';
        }

        $dto->codigoTributacaoMunicipal = $taxSettings['codigo_tributacao_municipio'] ?? '0107001';
        $dto->codigoCnae = $taxSettings['codigo_cnae'] ?? null;
        $dto->codigoNbs = $taxSettings['codigo_nbs'] ?? null;
        $dto->municipioPrestacaoIbge = '5300108';
        $dto->tributacaoIssqn = (int)($taxSettings['tributacao_issqn'] ?? 1);
        $dto->cstIbsCbs = $taxSettings['cst_ibs_cbs'] ?? '000';
        $dto->classificacaoTribIbsCbs = $taxSettings['classificacao_trib_ibs_cbs'] ?? '000000';
        $dto->indicadorOperacao = $taxSettings['indicador_operacao'] ?? '050101';

        // 4. Executa a emissão pelo provedor ativo
        $result = $this->provider->emitir($dto);

        // 5. Persiste o histórico em NfseEmissoes (blindado contra falha de gravação)
        try {
            $this->persistirEmissao($dto, $result, $idUsuarioResponsavel);
        } catch (\Throwable $t) {
            error_log("Aviso: Falha ao persistir histórico em NfseEmissoes: " . $t->getMessage());
        }

        // 6. Atualiza contadores e fatura se emitido com sucesso
        if ($result->isSuccess()) {
            $this->incrementarContador($ambiente, $isNacional, $nextNum);
            DBExecute($this->link, "UPDATE Faturas SET possui_nfse = 1, data_emissao_nfse = NOW() WHERE id_fatura = '$idFatura'");
        }

        return $result;
    }

    private function persistirEmissao(NfseData $dto, EmissionResult $result, ?int $idUsuario): void
    {
        $idFatura = (int)$dto->idFatura;
        $ambiente = mysqli_real_escape_string($this->link, $dto->ambiente);
        $provider = mysqli_real_escape_string($this->link, $this->provider->getProviderName());
        $valorServico = number_format($dto->valorServico, 2, '.', '');
        $aliquota = number_format($dto->aliquotaIss, 2, '.', '');
        $issRetido = $dto->issRetido ? 1 : 0;
        $itemLista = mysqli_real_escape_string($this->link, $dto->itemListaServico);
        $discriminacao = mysqli_real_escape_string($this->link, $dto->discriminacao);

        $numeroNota = mysqli_real_escape_string($this->link, $result->numeroNota ?: '');
        $codigoVerif = mysqli_real_escape_string($this->link, $result->codigoVerificacao ?: '');
        $chaveNfse = mysqli_real_escape_string($this->link, $result->chaveNfse ?: '');
        $idDps = mysqli_real_escape_string($this->link, $result->idDps ?: '');
        $urlPdf = mysqli_real_escape_string($this->link, $result->urlVisualizacao ?: '');
        $urlNacional = mysqli_real_escape_string($this->link, $result->urlVisualizacaoNacional ?: '');
        $status = mysqli_real_escape_string($this->link, $result->status);
        $msgErro = mysqli_real_escape_string($this->link, $result->details ?: $result->message);
        $xmlEnvio = mysqli_real_escape_string($this->link, $result->xmlEnvio ?: '');
        $xmlRetorno = mysqli_real_escape_string($this->link, $result->xmlRetorno ?: '');

        $colunasExtras = "";
        $valoresExtras = "";

        // Checa se as novas colunas já existem na tabela para evitar erros antes da migration
        $resCols = DBExecute($this->link, "SHOW COLUMNS FROM NfseEmissoes LIKE 'provider'");
        if ($resCols && mysqli_num_rows($resCols) > 0) {
            $colunasExtras = ", provider, id_dps, chave_nfse, url_visualizacao_nacional";
            $valoresExtras = ", '$provider', '$idDps', '$chaveNfse', '$urlNacional'";
        }

        $query = "INSERT INTO NfseEmissoes (
            id_fatura, id_usuario_responsavel, data_emissao, ambiente,
            valor_servico, aliquota_iss, iss_retido, item_lista_servico, discriminacao,
            numero_rps, serie_rps, numero_nota, codigo_verificacao, url_pdf,
            status, mensagem_erro, xml_envio, xml_retorno {$colunasExtras}
        ) VALUES (
            '{$idFatura}', " . ($idUsuario ? "'$idUsuario'" : "NULL") . ", NOW(), '{$ambiente}',
            '{$valorServico}', '{$aliquota}', '{$issRetido}', '{$itemLista}', '{$discriminacao}',
            '{$dto->numero}', '{$dto->serie}', '{$numeroNota}', '{$codigoVerif}', '{$urlPdf}',
            '{$status}', '{$msgErro}', '{$xmlEnvio}', '{$xmlRetorno}' {$valoresExtras}
        )";

        DBExecute($this->link, $query);
    }

    private function incrementarContador(string $ambiente, bool $isNacional, int $numero): void
    {
        $idConfig = (int)$this->config['id_config'];
        if ($isNacional) {
            $coluna = ($ambiente === 'producao') ? 'ultimo_dps_producao' : 'ultimo_dps_homologacao';
        } else {
            $coluna = ($ambiente === 'producao') ? 'ultimo_rps_producao' : 'ultimo_rps_homologacao';
        }

        // Checa se a coluna existe antes de atualizar
        $resCol = DBExecute($this->link, "SHOW COLUMNS FROM ConfiguracoesEmissor LIKE '$coluna'");
        if ($resCol && mysqli_num_rows($resCol) > 0) {
            DBExecute($this->link, "UPDATE ConfiguracoesEmissor SET {$coluna} = {$numero} WHERE id_config = {$idConfig}");
        }
    }
}
