<?php

namespace App\Listeners;

use App\Events\IncidentUpdated;
use Illuminate\Support\Facades\Log;

class LogIncidentUpdated
{
    public function handle(IncidentUpdated $event): void
    {
        Log::info('ORION incident updated', [
            'incident_id' => $event->incident->id,
            'action' => $event->action,
            'status' => $event->incident->status->value,
        ]);
    }
}
