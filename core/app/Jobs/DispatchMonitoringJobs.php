<?php

namespace App\Jobs;

use App\Models\Device;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

/**
 * DispatchMonitoringJobs — dispatch PingDeviceJob et PollSnmpJob pour tous les devices sans agent.
 *
 * Le Scheduler appelle ce Job chaque minute (ping) ; SNMP est dispatch toutes les 5 min
 * via un compteur ou un job separe DispatchSnmpJobsJob.
 *
 * Pattern "dispatcher" : 1 Job leger qui enqueue N Jobs unitaires = scalabilite NMS.
 */
class DispatchMonitoringJobs implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(public bool $includeSnmp = false)
    {
    }

    public function handle(): void
    {
        Device::query()
            ->whereDoesntHave('agent')
            ->pluck('id')
            ->each(function (int $deviceId) {
                PingDeviceJob::dispatch($deviceId);

                if ($this->includeSnmp) {
                    PollSnmpJob::dispatch($deviceId);
                }
            });
    }
}
