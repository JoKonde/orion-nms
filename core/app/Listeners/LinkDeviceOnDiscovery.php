<?php

namespace App\Listeners;

use App\Events\DeviceDiscovered;
use App\Services\TopologyService;

/**
 * Lie automatiquement un nouvel equipement Nmap au gateway de son sous-reseau.
 */
class LinkDeviceOnDiscovery
{
    public function __construct(private readonly TopologyService $topology)
    {
    }

    public function handle(DeviceDiscovered $event): void
    {
        $this->topology->linkNewDevice($event->device);
    }
}
