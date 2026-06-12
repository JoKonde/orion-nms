<?php

namespace App\Services\Monitoring;

use App\Enums\DeviceStatus;
use App\Enums\DeviceType;
use App\Enums\DiscoveryMethod;
use App\Events\DeviceDiscovered;
use App\Models\Device;
use Illuminate\Support\Facades\Log;
use Symfony\Component\Process\Process;

/**
 * NmapService — decouverte reseau et fingerprint basique.
 *
 * Utilise "nmap -sn" (ping scan) pour trouver les hotes actifs sur un sous-reseau.
 * Cree ou met a jour les devices avec discovery_method = nmap.
 */
class NmapService
{
    public function isAvailable(): bool
    {
        $process = new Process(['nmap', '--version']);
        $process->run();

        return $process->isSuccessful();
    }

    /**
     * Scan un sous-reseau et retourne les hotes decouverts.
     *
     * @return array<int, array{ip: string, mac: ?string, hostname: ?string}>
     */
    public function scanSubnet(string $subnet): array
    {
        if (! $this->isAvailable()) {
            Log::warning('Nmap non disponible — installez nmap sur le serveur ORION Core.');

            return [];
        }

        // -sn : ping scan (pas de scan de ports) — rapide et non intrusif pour un NMS.
        $process = new Process(['nmap', '-sn', '-oG', '-', $subnet]);
        $process->setTimeout(300);
        $process->run();

        if (! $process->isSuccessful()) {
            Log::warning('Nmap scan failed', ['subnet' => $subnet, 'error' => $process->getErrorOutput()]);

            return [];
        }

        $hosts = [];
        foreach (explode("\n", $process->getOutput()) as $line) {
            if (! str_starts_with($line, 'Host:')) {
                continue;
            }
            // Host: 192.168.1.1 () Status: Up
            if (preg_match('/Host:\s(\S+)\s.*Status:\sUp/i', $line, $m)) {
                $hosts[] = ['ip' => $m[1], 'mac' => null, 'hostname' => null];
            }
        }

        return $hosts;
    }

    /**
     * Persiste les hotes decouverts dans la table devices.
     *
     * @param  array<int, array{ip: string, mac: ?string, hostname: ?string}>  $hosts
     * @return int Nombre de nouveaux devices crees
     */
    public function persistDiscoveredHosts(array $hosts): int
    {
        $created = 0;

        foreach ($hosts as $host) {
            $existing = Device::where('ip_address', $host['ip'])->first();

            if ($existing) {
                $existing->update([
                    'status' => DeviceStatus::ONLINE,
                    'last_seen_at' => now(),
                ]);

                continue;
            }

            $device = Device::create([
                'name' => $host['hostname'] ?? 'Host '.$host['ip'],
                'ip_address' => $host['ip'],
                'mac_address' => $host['mac'],
                'type' => DeviceType::OTHER,
                'status' => DeviceStatus::ONLINE,
                'discovery_method' => DiscoveryMethod::NMAP,
                'last_seen_at' => now(),
            ]);

            DeviceDiscovered::dispatch($device);
            $created++;
        }

        return $created;
    }
}
