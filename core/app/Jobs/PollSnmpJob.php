<?php

namespace App\Jobs;

use App\Models\Device;
use App\Services\Monitoring\DeviceMonitoringService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

/**
 * PollSnmpJob — collecte SNMP (uptime, sysDescr, interfaces) pour un device.
 *
 * Planifie toutes les 5 minutes : le SNMP est plus lourd que le ping ICMP.
 * Les donnees alimentent vendor, uptime et la table device_interfaces.
 */
class PollSnmpJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(public int $deviceId)
    {
    }

    public function handle(DeviceMonitoringService $monitoring): void
    {
        $device = Device::query()
            ->whereDoesntHave('agent')
            ->find($this->deviceId);

        if (! $device) {
            return;
        }

        $monitoring->pollSnmp($device);
    }
}
