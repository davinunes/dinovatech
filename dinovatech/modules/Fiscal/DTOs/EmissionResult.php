<?php
namespace Dinovatech\Modules\Fiscal\DTOs;

class EmissionResult
{
    public bool $success = false;
    public string $status = 'erro'; // 'concluido', 'erro', 'processando'
    public string $message = '';
    public ?string $details = null;
    public ?string $numeroNota = null;
    public ?string $codigoVerificacao = null;
    public ?string $chaveNfse = null; // Chave de 50 dígitos no Padrão Nacional
    public ?string $idDps = null;      // Identificador de 45 dígitos da DPS
    public ?string $protocolo = null;
    public ?string $urlVisualizacao = null;
    public ?string $urlVisualizacaoNacional = null;
    public ?string $xmlEnvio = null;
    public ?string $xmlRetorno = null;
    public array $erros = [];

    public function isSuccess(): bool
    {
        return $this->success && $this->status === 'concluido';
    }
}
