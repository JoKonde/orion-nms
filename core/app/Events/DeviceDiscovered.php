<?php

namespace App\Events;

use App\Models\Device;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * DeviceDiscovered — declenche quand Nmap decouvre un nouvel equipement.
 *
 * Module 09 (Reverb) pourra broadcaster au dashboard en temps reel.
 * Module 08 (Topology) pourra creer des liens automatiques.
 */
class DeviceDiscovered
{
    use Dispatchable, SerializesModels;

    public function __construct(public Device $device)
    {
    }
}
