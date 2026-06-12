<?php

namespace App\Listeners;

use App\Events\AlertRaised;
use App\Services\IncidentService;
use Illuminate\Support\Facades\Log;

/**
 * Escalade automatique : alerte critical → incident ouvert.
 */
class EscalateCriticalAlertToIncident
{
    public function __construct(private readonly IncidentService $incidentService)
    {
    }

    public function handle(AlertRaised $event): void
    {
        $alert = $event->alert;

        if (! $this->incidentService->shouldAutoEscalate($alert)) {
            return;
        }

        $incident = $this->incidentService->createFromAlert(
            $alert,
            $this->incidentService->systemUser(),
            auto: true,
        );

        Log::info('ORION incident auto-escalated from alert', [
            'alert_id' => $alert->id,
            'incident_id' => $incident->id,
        ]);
    }
}
