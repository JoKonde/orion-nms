<?php

namespace Database\Seeders;

use App\Enums\DeviceStatus;
use App\Enums\DeviceType;
use App\Enums\DiscoveryMethod;
use App\Models\Device;
use Illuminate\Database\Seeder;

/**
 * DeviceSeeder — equipements de demonstration pour le developpement.
 */
class DeviceSeeder extends Seeder
{
    public function run(): void
    {
        $devices = [
            [
                'name' => 'Routeur principal',
                'ip_address' => '192.168.1.1',
                'mac_address' => '00:1A:2B:3C:4D:5E',
                'type' => DeviceType::ROUTER,
                'vendor' => 'Cisco',
                'model' => 'ISR 4331',
                'firmware' => '16.09.08',
                'status' => DeviceStatus::ONLINE,
                'discovery_method' => DiscoveryMethod::MANUAL,
            ],
            [
                'name' => 'Switch bureau',
                'ip_address' => '192.168.1.10',
                'mac_address' => '00:1B:2C:3D:4E:5F',
                'type' => DeviceType::SWITCH,
                'vendor' => 'HP',
                'model' => 'ProCurve 2530',
                'firmware' => 'Y.11.38',
                'status' => DeviceStatus::ONLINE,
                'discovery_method' => DiscoveryMethod::SNMP,
            ],
            [
                'name' => 'Serveur applicatif',
                'ip_address' => '192.168.1.50',
                'mac_address' => '00:1C:2D:3E:4F:60',
                'type' => DeviceType::SERVER,
                'vendor' => 'Dell',
                'model' => 'PowerEdge R740',
                'firmware' => null,
                'status' => DeviceStatus::UNKNOWN,
                'discovery_method' => DiscoveryMethod::MANUAL,
            ],
        ];

        foreach ($devices as $device) {
            Device::updateOrCreate(
                ['ip_address' => $device['ip_address']],
                $device
            );
        }
    }
}
