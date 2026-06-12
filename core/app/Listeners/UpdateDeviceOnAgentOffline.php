<?php

namespace App\Listeners;

use App\Enums\DeviceStatus;
use App\Events\AgentWentOffline;
use App\Events\DeviceWentOffline;

/**
 * Met a jour le device lie quand son agent passe offline.
 */
class UpdateDeviceOnAgentOffline
{
    public function handle(AgentWentOffline $event): void
    {
        $device = $event->agent->device;

        if (! $device) {
            return;
        }

        $previousStatus = $device->status;

        $device->update([
            'status' => DeviceStatus::OFFLINE,
        ]);

        if ($previousStatus !== DeviceStatus::OFFLINE) {
            DeviceWentOffline::dispatch($device->fresh());
        }
    }
}
