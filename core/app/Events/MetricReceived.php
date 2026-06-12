<?php

namespace App\Events;

use App\Models\Agent;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * MetricReceived — declenche apres ingestion d'un batch de metriques.
 *
 * Les Listeners futurs (Module 06 AlertEvaluator, Module 09 Reverb broadcast)
 * s'abonneront ici sans modifier MetricIngestionService.
 */
class MetricReceived
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public Agent $agent,
        public int $deviceId,
        public int $pointsCount,
    ) {
    }
}
