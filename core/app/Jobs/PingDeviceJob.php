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
 * PingDeviceJob — ping ICMP d'un equipement sans agent.
 *
 * POURQUOI un Job par device (et pas un ping direct dans le Scheduler) ?
 * -----------------------------------------------------------------------
 * Avec 500 routeurs/switchs, un ping sequentiel bloquerait le cron 5+ minutes.
 * Chaque PingDeviceJob est mis en file Redis ; les workers les executent en
 * parallele (php artisan queue:work). Le Scheduler ne fait que dispatcher.
 *
 * On exclut les devices avec agent : leur disponibilite est geree par heartbeat.
 */
class PingDeviceJob implements ShouldQueue
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

        $monitoring->ping($device);
    }
}
