<?php

namespace App\Listeners;

use App\Events\AgentWentOffline;
use Illuminate\Support\Facades\Log;

/**
 * Journalise la perte de connexion d'un agent (audit / debug).
 */
class LogAgentOffline
{
    public function handle(AgentWentOffline $event): void
    {
        Log::warning('ORION Agent offline', [
            'agent_uuid' => $event->agent->agent_uuid,
            'hostname' => $event->agent->hostname,
            'last_seen_at' => $event->agent->last_seen_at,
        ]);
    }
}
