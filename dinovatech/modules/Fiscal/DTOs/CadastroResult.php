<?php
namespace Dinovatech\Modules\Fiscal\DTOs;

class CadastroResult
{
    public bool $success = false;
    public string $message = '';
    public ?string $cnpj = null;
    public ?string $im = null;
    public ?string $statusCadastro = null;
    public ?string $razaoSocial = null;
    public ?string $nomeFantasia = null;
    public ?string $logradouro = null;
    public ?string $bairro = null;
    public ?string $codigoMunicipio = null;
    public ?string $uf = null;
    public ?string $cep = null;
    public ?string $telefone = null;
    public ?string $email = null;
    public bool $emiteNfse = false;
    public bool $optanteSimples = false;
    public ?string $dataSimples = null;
    public bool $optanteMei = false;
    public bool $permiteDescontoCondicionado = false;
    public bool $permiteDescontoIncondicionado = false;
    public array $tributacoesPermitidas = [];
    public array $atividades = []; // Lista de todas as atividades
    public array $atividadesVigentes = []; // Lista filtrada apenas com vigência ativa
    public ?string $xmlRetorno = null;
    public ?string $envelopeEnvio = null;
    public array $erros = [];
}
