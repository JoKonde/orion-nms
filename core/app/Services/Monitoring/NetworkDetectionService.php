<?php

namespace App\Services\Monitoring;

use Symfony\Component\Process\Process;

/**
 * NetworkDetectionService — detecte l'IP locale et le sous-reseau du serveur ORION.
 *
 * Si ORION_DISCOVERY_SUBNET est vide dans .env, on calcule automatiquement le CIDR
 * a partir des interfaces reseau (ipconfig sous Windows, ip addr sous Linux).
 *
 * Le dashboard React utilisera GET /network/detected pour proposer :
 *   "Réseau détecté : 192.168.1.0/24 — Scanner ?"
 */
class NetworkDetectionService
{
    /** Sous-reseau de secours si rien n'est detecte (labo / dev). */
    private const FALLBACK_SUBNET = '192.168.1.0/24';

    /**
     * Interfaces detectees sur cette machine.
     *
     * @return array<int, array{name: string, ip: string, netmask: string, subnet: string}>
     */
    public function detectLocalInterfaces(): array
    {
        $isWindows = strtoupper(substr(PHP_OS, 0, 3)) === 'WIN';

        return $isWindows
            ? $this->parseWindowsIpconfig()
            : $this->parseLinuxIpAddr();
    }

    /**
     * Retourne le sous-reseau effectif pour Nmap + sa provenance.
     *
     * Priorite :
     *   1. ORION_DISCOVERY_SUBNET dans .env (si renseigne)
     *   2. Auto-detection (premiere interface privee valide)
     *   3. Fallback constant (dev)
     *
     * @return array{subnet: string, source: string, message: string}
     */
    public function resolveDiscoverySubnet(?string $override = null): array
    {
        if ($override) {
            return $this->buildResult($override, 'manual', 'Sous-reseau fourni manuellement.');
        }

        $configured = config('orion.monitoring.default_subnet');
        if (! empty($configured)) {
            return $this->buildResult(
                $configured,
                'env',
                "Sous-reseau configure dans .env (ORION_DISCOVERY_SUBNET)."
            );
        }

        $interfaces = $this->detectLocalInterfaces();
        if ($interfaces !== []) {
            $primary = $interfaces[0];

            return $this->buildResult(
                $primary['subnet'],
                'auto_detect',
                "Réseau détecté automatiquement : {$primary['subnet']} (interface {$primary['name']}, IP {$primary['ip']})."
            );
        }

        return $this->buildResult(
            self::FALLBACK_SUBNET,
            'default',
            'Aucune interface detectee — utilisation du sous-reseau par defaut. Configurez ORION_DISCOVERY_SUBNET dans .env.'
        );
    }

    /**
     * Contexte complet pour le dashboard (detection + config + aide saisie subnet).
     *
     * @return array<string, mixed>
     */
    public function getDetectionContext(): array
    {
        $interfaces = $this->detectLocalInterfaces();
        $resolved = $this->resolveDiscoverySubnet();
        $configured = config('orion.monitoring.default_subnet');

        return [
            'detected_interfaces' => $interfaces,
            'configured_subnet' => $configured ?: null,
            'effective_subnet' => $resolved['subnet'],
            'source' => $resolved['source'],
            'scan_prompt' => "Réseau détecté : {$resolved['subnet']} — Scanner ?",
            'message' => $resolved['message'],
            // Aide pour le champ subnet du dashboard React (saisie manuelle par l'admin).
            'subnet_help' => [
                'windows' => 'Ouvrez cmd (Invite de commandes) et tapez : ipconfig — repérez « Adresse IPv4 » et « Masque de sous-réseau », puis saisissez le CIDR (ex: 192.168.1.0/24).',
                'linux' => 'Ouvrez un terminal et tapez : ip addr (ou ifconfig) — repérez « inet » sur l\'interface active, puis saisissez le CIDR (ex: 192.168.1.0/24).',
                'example' => '192.168.1.0/24',
            ],
        ];
    }

    /**
     * @return array{subnet: string, source: string, message: string}
     */
    private function buildResult(string $subnet, string $source, string $message): array
    {
        return [
            'subnet' => $subnet,
            'source' => $source,
            'message' => $message,
        ];
    }

    /**
     * @return array<int, array{name: string, ip: string, netmask: string, subnet: string}>
     */
    private function parseWindowsIpconfig(): array
    {
        $process = new Process(['ipconfig']);
        $process->run();

        if (! $process->isSuccessful()) {
            return [];
        }

        $interfaces = [];
        $currentName = 'Interface';
        $currentIp = null;
        $currentMask = null;

        foreach (explode("\n", $process->getOutput()) as $line) {
            $line = rtrim($line);

            if ($line === '') {
                continue;
            }

            // En-tete adaptateur : pas d'indentation, pas de pointilles " . . " (champs ipconfig).
            if ($this->isWindowsAdapterHeaderLine($line)) {
                if ($currentIp && $currentMask && $this->isUsableIp($currentIp)) {
                    $interfaces[] = $this->makeInterface($currentName, $currentIp, $currentMask);
                }
                $currentName = rtrim($line, ': ');
                $currentIp = null;
                $currentMask = null;

                continue;
            }

            if (preg_match('/IPv4.*:\s*([\d.]+)/i', $line, $m) || preg_match('/Adresse IPv4.*:\s*([\d.]+)/i', $line, $m)) {
                $currentIp = $m[1];
            }

            if (preg_match('/Masque.*:\s*([\d.]+)/i', $line, $m) || preg_match('/Subnet Mask.*:\s*([\d.]+)/i', $line, $m)) {
                $currentMask = $m[1];
            }
        }

        if ($currentIp && $currentMask && $this->isUsableIp($currentIp)) {
            $interfaces[] = $this->makeInterface($currentName, $currentIp, $currentMask);
        }

        return $interfaces;
    }

    /**
     * @return array<int, array{name: string, ip: string, netmask: string, subnet: string}>
     */
    private function parseLinuxIpAddr(): array
    {
        $process = new Process(['ip', '-4', '-o', 'addr', 'show']);
        $process->run();

        if (! $process->isSuccessful()) {
            return [];
        }

        $interfaces = [];
        foreach (explode("\n", trim($process->getOutput())) as $line) {
            // 2: eth0    inet 192.168.1.50/24 brd ...
            if (! preg_match('/^\d+:\s(\S+)\s+inet\s+([\d.]+)\/(\d+)/', $line, $m)) {
                continue;
            }

            $ip = $m[2];
            if (! $this->isUsableIp($ip)) {
                continue;
            }

            $prefix = (int) $m[3];
            $netmask = $this->prefixToNetmask($prefix);
            $interfaces[] = [
                'name' => $m[1],
                'ip' => $ip,
                'netmask' => $netmask,
                'subnet' => $this->ipAndPrefixToCidr($ip, $prefix),
            ];
        }

        return $interfaces;
    }

    /**
     * @return array{name: string, ip: string, netmask: string, subnet: string}
     */
    private function makeInterface(string $name, string $ip, string $netmask): array
    {
        return [
            'name' => $name,
            'ip' => $ip,
            'netmask' => $netmask,
            'subnet' => $this->ipAndNetmaskToCidr($ip, $netmask),
        ];
    }

    private function isWindowsAdapterHeaderLine(string $line): bool
    {
        if (str_starts_with($line, ' ') || str_starts_with($line, "\t")) {
            return false;
        }

        if (! str_ends_with($line, ':')) {
            return false;
        }

        // Lignes de champs ipconfig contiennent des pointilles avant les deux-points.
        if (preg_match('/\. \./', $line)) {
            return false;
        }

        $lower = strtolower($line);

        return ! str_contains($lower, 'configuration ip')
            && ! str_contains($lower, 'windows ip');
    }

    private function isUsableIp(string $ip): bool
    {
        if ($ip === '127.0.0.1' || str_starts_with($ip, '169.254.')) {
            return false;
        }

        return filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4) !== false;
    }

    private function ipAndNetmaskToCidr(string $ip, string $netmask): string
    {
        $prefix = substr_count(decbin(ip2long($netmask)), '1');

        return $this->ipAndPrefixToCidr($ip, $prefix);
    }

    private function ipAndPrefixToCidr(string $ip, int $prefix): string
    {
        $mask = -1 << (32 - $prefix);
        $network = long2ip(ip2long($ip) & $mask);

        return "{$network}/{$prefix}";
    }

    private function prefixToNetmask(int $prefix): string
    {
        $mask = -1 << (32 - $prefix);

        return long2ip($mask);
    }
}
