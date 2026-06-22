<?php

namespace App\Listeners;

use App\Events\DeviceBackOnline;
use App\Events\DeviceWentOffline;
use App\Services\TopologyService;

/**
 * Met a jour le statut des liens quand un device passe offline/online.
 */
class RefreshTopologyOnDeviceStatus
{
    public function __construct(private readonly TopologyService $topology)
    {
    }

    public function handle(DeviceWentOffline|DeviceBackOnline $event): void
    {
        $this->topology->refreshLinksForDevice($event->device);
    }
}
