<?php

namespace App\Listeners;

use App\Events\DeviceDiscovered;
use Illuminate\Support\Facades\Log;

class LogDeviceDiscovered
{
    public function handle(DeviceDiscovered $event): void
    {
        Log::info('ORION device discovered', [
            'device_id' => $event->device->id,
            'ip' => $event->device->ip_address,
            'method' => $event->device->discovery_method?->value,
        ]);
    }
}
