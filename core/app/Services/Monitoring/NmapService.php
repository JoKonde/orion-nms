<?php

namespace App\Services\Monitoring;

use App\Enums\DeviceStatus;
use App\Enums\DeviceType;
use App\Enums\DiscoveryMethod;
use App\Events\DeviceDiscovered;
use App\Models\Device;
use App\Support\ProcessHelper;
use App\Support\TextEncoding;
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
    private ?string $resolvedBinary = null;

    public function isAvailable(): bool
    {
        return $this->resolveBinary() !== null;
    }

    /**
     * Chemin vers nmap.exe ou binaire "nmap" du PATH.
     */
    public function resolveBinary(): ?string
    {
        if ($this->resolvedBinary !== null) {
            return $this->resolvedBinary !== '' ? $this->resolvedBinary : null;
        }

        $configured = config('orion.monitoring.nmap_binary');
        if (is_string($configured) && $configured !== '') {
            $normalized = $this->normalizeExistingPath($configured);
            if ($normalized !== null) {
                return $this->resolvedBinary = $normalized;
            }
        }

        $process = ProcessHelper::make(['nmap', '--version'], timeout: 30);
        $process->run();
        if ($process->isSuccessful()) {
            return $this->resolvedBinary = 'nmap';
        }

        foreach ($this->defaultBinaryCandidates() as $path) {
            if (is_file($path)) {
                return $this->resolvedBinary = $path;
            }
        }

        $this->resolvedBinary = '';

        return null;
    }

    /**
     * Scan complet : Nmap + persistance BDD + stats detaillees (CLI, API, scheduler).
     *
     * @return array{
     *     success: bool,
     *     hosts_found: int,
     *     devices_created: int,
     *     devices_updated: int,
     *     devices_removed: int,
     *     nmap_binary: ?string,
     *     error: ?string
     * }
     */
    public function discoverSubnet(string $subnet, bool $forceDirect = false): array
    {
        if (! $forceDirect && $this->shouldScanViaArtisanSubprocess()) {
            return $this->discoverViaArtisanSubprocess($subnet);
        }

        return $this->discoverSubnetDirect($subnet);
    }

    /**
     * Sous Windows, php artisan serve casse les sockets Nmap (Npcap/Winsock).
     * On delegue au CLI artisan dans un processus frais — meme flux que orion:network-detect --scan.
     */
    private function shouldScanViaArtisanSubprocess(): bool
    {
        return PHP_OS_FAMILY === 'Windows' && ! app()->runningInConsole();
    }

    /**
     * @return array{
     *     success: bool,
     *     hosts_found: int,
     *     devices_created: int,
     *     devices_updated: int,
     *     devices_removed: int,
     *     nmap_binary: ?string,
     *     error: ?string,
     *     warning: ?string
     * }
     */
    private function discoverViaArtisanSubprocess(string $subnet): array
    {
        $process = ProcessHelper::make([
            PHP_BINARY,
            base_path('artisan'),
            'orion:discover',
            $subnet,
            '--json',
            '--direct',
            '--no-ansi',
        ], base_path(), 320);

        try {
            $process->run();
        } catch (\Throwable $e) {
            return $this->failureResult('Impossible de lancer le scan reseau : '.$e->getMessage());
        }

        $output = trim(TextEncoding::toUtf8($process->getOutput()) ?? '');
        $decoded = json_decode($output, true);

        if (! is_array($decoded)) {
            $stderr = TextEncoding::toUtf8(trim($process->getErrorOutput())) ?? '';

            return $this->failureResult(
                'Scan reseau echoue (sous-processus).'.($stderr !== '' ? " {$stderr}" : '')
            );
        }

        return $decoded;
    }

    /**
     * @return array{
     *     success: bool,
     *     hosts_found: int,
     *     devices_created: int,
     *     devices_updated: int,
     *     devices_removed: int,
     *     nmap_binary: ?string,
     *     error: ?string,
     *     warning: ?string
     * }
     */
    private function discoverSubnetDirect(string $subnet): array
    {
        $binary = $this->resolveBinary();
        if ($binary === null) {
            return $this->failureResult(
                'Nmap introuvable. Definissez ORION_NMAP_BINARY dans .env (ex: C:/Program Files (x86)/Nmap/nmap.exe) puis redemarrez php artisan serve.'
            );
        }

        try {
            $process = $this->runProcess(['-sn', '-oG', '-', $subnet]);
        } catch (\RuntimeException $e) {
            return $this->failureResult($e->getMessage(), $binary);
        }

        if (! $process->isSuccessful()) {
            $details = TextEncoding::toUtf8(trim($process->getErrorOutput()) ?: trim($process->getOutput())) ?? '';
            Log::warning('Nmap scan failed', [
                'subnet' => $subnet,
                'binary' => $binary,
                'error' => $details,
            ]);

            return $this->failureResult(
                'Echec du scan Nmap.'.($details !== '' ? " {$details}" : ''),
                $binary
            );
        }

        $hosts = $this->parseGrepableOutput(TextEncoding::toUtf8($process->getOutput()) ?? $process->getOutput());
        [$created, $updated] = $this->persistDiscoveredHosts($hosts);
        $removed = $this->removeStaleNmapHosts($subnet, array_column($hosts, 'ip'));

        $result = [
            'success' => true,
            'hosts_found' => count($hosts),
            'devices_created' => $created,
            'devices_updated' => $updated,
            'devices_removed' => $removed,
            'nmap_binary' => TextEncoding::toUtf8($binary),
            'error' => null,
            'warning' => null,
        ];

        if (count($hosts) === 0) {
            $result['warning'] = 'Nmap a termine sans trouver d\'hote actif. Verifiez le subnet, Npcap, ou relancez php artisan serve apres avoir configure ORION_NMAP_BINARY.';
        }

        return $result;
    }

    /**
     * @return array{0: int, 1: int} [created, updated]
     */
    public function persistDiscoveredHosts(array $hosts): array
    {
        $created = 0;
        $updated = 0;

        foreach ($hosts as $host) {
            $existing = Device::where('ip_address', $host['ip'])->first();

            if ($existing) {
                if ($existing->agent()->exists() || $existing->discovery_method === DiscoveryMethod::AGENT) {
                    continue;
                }

                $existing->update([
                    'status' => DeviceStatus::ONLINE,
                    'last_seen_at' => now(),
                ]);
                $updated++;

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

            try {
                DeviceDiscovered::dispatch($device);
            } catch (\Throwable $e) {
                // Le scan ne doit pas echouer si Reverb/broadcast est indisponible.
                Log::warning('DeviceDiscovered broadcast skipped', [
                    'device_id' => $device->id,
                    'error' => $e->getMessage(),
                ]);
            }

            $created++;
        }

        return [$created, $updated];
    }

    /**
     * Supprime les devices decouverts par Nmap sur le subnet scanne mais absents du dernier scan.
     */
    public function removeStaleNmapHosts(string $subnet, array $foundIps): int
    {
        /** @var NetworkDetectionService $networkDetection */
        $networkDetection = app(NetworkDetectionService::class);
        $foundSet = array_flip($foundIps);
        $removed = 0;

        Device::query()
            ->where('discovery_method', DiscoveryMethod::NMAP)
            ->each(function (Device $device) use ($subnet, $foundSet, $networkDetection, &$removed): void {
                $ip = $device->ip_address;

                if ($ip === null || ! $networkDetection->ipBelongsToSubnet($ip, $subnet)) {
                    return;
                }

                if (isset($foundSet[$ip])) {
                    return;
                }

                $device->delete();
                $removed++;
            });

        return $removed;
    }

    /**
     * @return array<int, string>
     */
    private function defaultBinaryCandidates(): array
    {
        if (PHP_OS_FAMILY !== 'Windows') {
            return ['/usr/bin/nmap', '/usr/local/bin/nmap'];
        }

        return [
            'C:\\Program Files (x86)\\Nmap\\nmap.exe',
            'C:\\Program Files\\Nmap\\nmap.exe',
        ];
    }

    private function normalizeExistingPath(string $path): ?string
    {
        $path = trim($path, " \t\n\r\0\x0B\"'");

        foreach ([$path, str_replace('/', '\\', $path), str_replace('\\', '/', $path)] as $candidate) {
            if (is_file($candidate)) {
                return $candidate;
            }
        }

        $realpath = realpath($path);

        return $realpath !== false && is_file($realpath) ? $realpath : null;
    }

    /**
     * @param  array<int, string>  $arguments
     */
    private function runProcess(array $arguments): Process
    {
        $binary = $this->resolveBinary();
        if ($binary === null) {
            throw new \RuntimeException('Nmap introuvable.');
        }

        $process = ProcessHelper::make(array_merge([$binary], $arguments));
        $process->run();

        return $process;
    }

    /**
     * @return array<int, array{ip: string, mac: ?string, hostname: ?string}>
     */
    private function parseGrepableOutput(string $output): array
    {
        $hosts = [];

        foreach (explode("\n", $output) as $line) {
            if (! str_starts_with($line, 'Host:')) {
                continue;
            }

            if (preg_match('/Host:\s(\S+)\s.*Status:\sUp/i', $line, $matches)) {
                $hosts[] = ['ip' => $matches[1], 'mac' => null, 'hostname' => null];
            }
        }

        return $hosts;
    }

    /**
     * @return array{
     *     success: bool,
     *     hosts_found: int,
     *     devices_created: int,
     *     devices_updated: int,
     *     devices_removed: int,
     *     nmap_binary: ?string,
     *     error: ?string
     * }
     */
    private function failureResult(string $error, ?string $binary = null): array
    {
        Log::warning('Nmap discovery failed', ['error' => $error, 'binary' => $binary]);

        return [
            'success' => false,
            'hosts_found' => 0,
            'devices_created' => 0,
            'devices_updated' => 0,
            'devices_removed' => 0,
            'nmap_binary' => TextEncoding::toUtf8($binary),
            'error' => TextEncoding::toUtf8($error),
            'warning' => null,
        ];
    }
}
