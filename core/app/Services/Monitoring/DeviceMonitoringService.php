<?php

namespace App\Services\Monitoring;

use App\Enums\DeviceStatus;
use App\Events\DeviceBackOnline;
use App\Events\DeviceWentOffline;
use App\Models\Device;

/**
 * DeviceMonitoringService — orchestre ping/SNMP sur un device existant.
 */
class DeviceMonitoringService
{
    public function __construct(
        private readonly PingService $pingService,
        private readonly SnmpService $snmpService,
    ) {
    }

    /**
     * Ping ICMP et met a jour le statut du device.
     */
    public function ping(Device $device): bool
    {
        $previousStatus = $device->status;
        $reachable = $this->pingService->isReachable($device->ip_address);

        $device->update([
            'status' => $reachable ? DeviceStatus::ONLINE : DeviceStatus::OFFLINE,
            'last_seen_at' => $reachable ? now() : $device->last_seen_at,
        ]);

        $this->dispatchStatusEvents($device->fresh(), $previousStatus);

        return $reachable;
    }

    /**
     * Poll SNMP ; si succes, device passe online.
     */
    public function pollSnmp(Device $device): bool
    {
        $previousStatus = $device->status;
        $result = $this->snmpService->poll($device);

        if ($result['success']) {
            $device->update([
                'status' => DeviceStatus::ONLINE,
                'last_seen_at' => now(),
            ]);

            $this->dispatchStatusEvents($device->fresh(), $previousStatus);

            return true;
        }

        return false;
    }

    /**
     * Declenche les Events offline/online pour le Module 06 Alerting.
     */
    private function dispatchStatusEvents(Device $device, DeviceStatus $previousStatus): void
    {
        if ($previousStatus !== DeviceStatus::OFFLINE && $device->status === DeviceStatus::OFFLINE) {
            DeviceWentOffline::dispatch($device);
        }

        if ($previousStatus === DeviceStatus::OFFLINE && $device->status === DeviceStatus::ONLINE) {
            DeviceBackOnline::dispatch($device);
        }
    }
}
