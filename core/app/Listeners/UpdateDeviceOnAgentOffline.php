<?php

namespace App\Listeners;

use App\Enums\DeviceStatus;
use App\Events\AgentWentOffline;

/**
 * Met a jour le device lie quand son agent passe offline.
 */
class UpdateDeviceOnAgentOffline
{
    public function handle(AgentWentOffline $event): void
    {
        $event->agent->device?->update([
            'status' => DeviceStatus::OFFLINE,
        ]);
    }
}
