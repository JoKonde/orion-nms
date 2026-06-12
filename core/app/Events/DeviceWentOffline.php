<?php

namespace App\Events;

use App\Models\Device;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * DeviceWentOffline — device passe de online/unknown a offline.
 *
 * Declenche l'evaluation des regles device_offline (Module 06).
 */
class DeviceWentOffline
{
    use Dispatchable, SerializesModels;

    public function __construct(public Device $device)
    {
    }
}
