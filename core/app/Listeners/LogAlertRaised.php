<?php

namespace App\Listeners;

use App\Events\AlertRaised;
use Illuminate\Support\Facades\Log;

/**
 * LogAlertRaised — trace les alertes en log (Module 09 ajoutera Reverb broadcast).
 */
class LogAlertRaised
{
    public function handle(AlertRaised $event): void
    {
        $alert = $event->alert;

        Log::warning('ORION alert raised', [
            'alert_id' => $alert->id,
            'device_id' => $alert->device_id,
            'severity' => $alert->severity->value,
            'title' => $alert->title,
        ]);
    }
}
