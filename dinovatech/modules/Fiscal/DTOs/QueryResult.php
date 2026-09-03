<?php
namespace Dinovatech\Modules\Fiscal\DTOs;

class QueryResult
{
    public bool $success = false;
    public bool $encontrada = false;
    public string $message = '';
    public ?string $numeroNota = null;
    public ?string $codigoVerificacao = null;
    public ?string $chaveNfse = null;
    public ?string $urlVisualizacao = null;
    public ?string $xmlRetorno = null;
    public array $erros = [];
}
