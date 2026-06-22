<?php

namespace App\Services;

use App\Data\HeartbeatData;
use App\Enums\AgentStatus;
use App\Enums\DeviceStatus;
use App\Events\AgentStatusChanged;
use App\Models\Agent;
use App\Models\Heartbeat;
use Illuminate\Support\Facades\DB;

/**
 * AgentHeartbeatService — traite les signaux de vie des agents.
 *
 * A chaque heartbeat :
 *   - Met a jour last_seen_at et status online
 *   - Enregistre l'historique dans la table heartbeats
 *   - Met a jour le device lie (last_seen_at, status online)
 */
class AgentHeartbeatService
{
    public function record(Agent $agent, HeartbeatData $data): Agent
    {
        return DB::transaction(function () use ($agent, $data) {
            $now = now();
            $previousStatus = $agent->status->value;

            $agent->update([
                'status' => AgentStatus::ONLINE,
                'last_seen_at' => $now,
            ]);

            Heartbeat::create([
                'agent_id' => $agent->id,
                'payload' => $data->payload,
                'created_at' => $now,
            ]);

            $agent->device?->update([
                'status' => DeviceStatus::ONLINE,
                'last_seen_at' => $now,
            ]);

            $fresh = $agent->fresh()->load('device');

            if ($previousStatus === AgentStatus::OFFLINE->value) {
                AgentStatusChanged::dispatch($fresh, $previousStatus);
            }

            return $fresh;
        });
    }
}
