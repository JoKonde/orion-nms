<?php

namespace App\Services\Monitoring;

use App\Enums\DeviceStatus;
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
        $reachable = $this->pingService->isReachable($device->ip_address);

        $device->update([
            'status' => $reachable ? DeviceStatus::ONLINE : DeviceStatus::OFFLINE,
            'last_seen_at' => $reachable ? now() : $device->last_seen_at,
        ]);

        return $reachable;
    }

    /**
     * Poll SNMP ; si succes, device passe online.
     */
    public function pollSnmp(Device $device): bool
    {
        $result = $this->snmpService->poll($device);

        if ($result['success']) {
            $device->update([
                'status' => DeviceStatus::ONLINE,
                'last_seen_at' => now(),
            ]);

            return true;
        }

        return false;
    }
}
