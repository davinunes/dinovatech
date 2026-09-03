<?php
namespace Dinovatech\Modules\Fiscal\DTOs;

class CancellationResult
{
    public bool $success = false;
    public string $message = '';
    public ?string $protocoloEvento = null;
    public ?string $xmlEnvio = null;
    public ?string $xmlRetorno = null;
    public array $erros = [];
}
