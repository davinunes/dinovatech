<?php
namespace Dinovatech\Modules\Fiscal\DTOs;

class UrlResult
{
    public bool $success = false;
    public string $message = '';
    public ?string $urlVisualizacao = null;
    public ?string $urlVerificacaoAutenticidade = null;
    public ?string $urlVisualizacaoNacional = null;
    public ?string $xmlRetorno = null;
}
