<?php

namespace App\Listeners;

use App\Events\MetricReceived;
use App\Services\AlertEvaluator;

/**
 * EvaluateAlertsOnMetricReceived — evalue les seuils apres ingestion metriques.
 *
 * Branché sur MetricReceived pour ne pas modifier MetricIngestionService
 * a chaque nouvelle regle d'alerte.
 */
class EvaluateAlertsOnMetricReceived
{
    public function __construct(private readonly AlertEvaluator $evaluator)
    {
    }

    public function handle(MetricReceived $event): void
    {
        $this->evaluator->evaluateMetrics($event->deviceId, $event->points);
    }
}
