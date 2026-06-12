<?php

namespace App\Data;

use Spatie\LaravelData\Attributes\Validation\Required;
use Spatie\LaravelData\Attributes\Validation\Uuid;
use Spatie\LaravelData\Data;

/**
 * MetricBatchData — DTO pour l'ingestion bulk des metriques agent.
 *
 * POURQUOI Spatie Laravel Data pour les metriques ?
 * -------------------------------------------------
 * L'agent envoie un tableau JSON "batch" avec des dizaines/centaines de points
 * (surtout apres une coupure reseau — resync SQLite -> Core).
 *
 * MetricBatchData :
 *   - Valide automatiquement chaque point (type, value, recorded_at)
 *   - Convertit "cpu" string -> MetricType::CPU (enum type)
 *   - Garantit un format uniforme avant insertion en base
 *
 * Sans DTO : boucles manuelles, isset(), risque d'erreurs sur des milliers de points/jour.
 */
class MetricBatchData extends Data
{
    /**
     * @param  array<int, MetricPointData>  $batch
     */
    public function __construct(
        #[Required, Uuid]
        public string $agent_uuid,

        public array $batch,
    ) {
    }
}
