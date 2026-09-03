<?php
namespace Dinovatech\Modules\Fiscal\Contracts;

use Dinovatech\Modules\Fiscal\DTOs\NfseData;
use Dinovatech\Modules\Fiscal\DTOs\EmissionResult;
use Dinovatech\Modules\Fiscal\DTOs\QueryResult;
use Dinovatech\Modules\Fiscal\DTOs\CancellationResult;
use Dinovatech\Modules\Fiscal\DTOs\UrlResult;

interface NfseProviderInterface
{
    /**
     * Retorna o identificador do provedor ('legacy' ou 'nacional')
     */
    public function getProviderName(): string;

    /**
     * Emite a NFS-e síncrona
     */
    public function emitir(NfseData $data): EmissionResult;

    /**
     * Consulta a NFS-e a partir da identificação provisória (DPS no nacional, RPS no legado)
     */
    public function consultarPorDocumento(string $serie, int $numero): QueryResult;

    /**
     * Obtém as URLs oficiais de visualização e autenticidade da nota
     */
    public function consultarUrl(string $numeroNota, ?string $serie = null, ?int $numeroDocumento = null): UrlResult;

    /**
     * Cancela uma NFS-e emitida
     */
    public function cancelar(string $identificadorNota, int $codigoMotivo, string $justificativa): CancellationResult;
}
