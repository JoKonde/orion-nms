<?php

namespace App\Listeners;

use App\Events\MetricReceived;
use Illuminate\Support\Facades\Log;

/**
 * Journalise l'ingestion de metriques (audit / debug).
 *
 * Module 06 : EvaluateAlertsOnMetricReceived evalue les seuils ici.
 * Module 09 ajoutera un Listener BroadcastMetricToDashboard.
 */
class LogMetricReceived
{
    public function handle(MetricReceived $event): void
    {
        Log::info('ORION metrics ingested', [
            'agent_uuid' => $event->agent->agent_uuid,
            'device_id' => $event->deviceId,
            'points' => $event->pointsCount,
        ]);
    }
}
