<?php

namespace App\Events;

use App\Models\Device;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * DeviceBackOnline — device repasse online (auto-resout alertes offline).
 */
class DeviceBackOnline
{
    use Dispatchable, SerializesModels;

    public function __construct(public Device $device)
    {
    }
}
