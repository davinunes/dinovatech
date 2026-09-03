<?php
namespace Dinovatech\Modules\Fiscal\DTOs;

/**
 * Objeto de transferência com todos os dados de domínio necessários para emissão da NFS-e.
 * Agnóstico quanto ao padrão fiscal de saída (ABRASF ou Nacional).
 */
class NfseData
{
    public int $idFatura;
    public string $ambiente; // 'producao' ou 'homologacao'
    public string $serie;
    public int $numero;
    public string $dataCompetencia; // YYYY-MM-DD
    public float $valorServico;
    public float $aliquotaIss; // Ex: 2.00
    public bool $issRetido;

    // Dados do Prestador
    public string $prestadorCnpj;
    public string $prestadorInscricaoMunicipal;
    public ?string $prestadorInscricaoEstadual = null;
    public string $prestadorRazaoSocial;
    public string $prestadorNomeFantasia;
    public string $prestadorRegimeTributario; // 'simples', etc.
    public bool $prestadorOptanteSimples;
    public string $prestadorMunicipioIbge; // '5300108'

    // Dados do Tomador
    public ?string $tomadorCpfCnpj = null;
    public ?string $tomadorTipoDocumento = null; // 'CPF' ou 'CNPJ'
    public ?string $tomadorInscricaoMunicipal = null;
    public string $tomadorRazaoSocial;
    public ?string $tomadorEmail = null;
    public ?string $tomadorTelefone = null;
    public ?string $tomadorLogradouro = null;
    public ?string $tomadorNumero = null;
    public ?string $tomadorComplemento = null;
    public ?string $tomadorBairro = null;
    public ?string $tomadorMunicipioIbge = null;
    public ?string $tomadorUf = null;
    public ?string $tomadorCep = null;

    // Dados do Serviço
    public string $discriminacao;
    public string $itemListaServico; // Ex: '01.07' ou '1.07'
    public string $codigoTributacaoNacional; // Ex: '010701' (6 dígitos)
    public string $codigoTributacaoMunicipal; // Ex: '0107001'
    public ?string $codigoCnae = null;
    public ?string $codigoNbs = null; // Ex: '114032110' (9 dígitos)
    public string $municipioPrestacaoIbge; // Ex: '5300108'
    public int $tributacaoIssqn = 1; // 1=Tributavel, 2=Imunidade, 3=Exportacao, 4=Nao Incidencia

    // Reforma Tributária / IBS e CBS (Obrigatório pós 01/10/2026)
    public ?string $indicadorOperacao = '050101'; // Ex: '050101'
    public ?string $cstIbsCbs = null;
    public ?string $classificacaoTribIbsCbs = null;
    public ?string $meioPagamento = null; // Ex: '17' para PIX, '03' Cartão

    public static function fromArray(array $data): self
    {
        $dto = new self();
        foreach ($data as $key => $value) {
            if (property_exists($dto, $key)) {
                $dto->{$key} = $value;
            }
        }
        return $dto;
    }
}
