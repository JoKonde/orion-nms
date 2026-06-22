<?php

namespace App\Console\Commands;

use App\Services\Monitoring\NetworkDetectionService;
use App\Services\Monitoring\NmapService;
use Illuminate\Console\Command;

/**
 * Affiche le reseau detecte au demarrage / premiere config CLI.
 *
 * Usage :
 *   php artisan orion:network-detect
 *   php artisan orion:network-detect --scan
 */
class NetworkDetectCommand extends Command
{
    protected $signature = 'orion:network-detect {--scan : Lancer le scan Nmap sur le subnet effectif}';

    protected $description = 'Detecte l\'IP locale et le sous-reseau ORION (premiere configuration)';

    public function handle(NetworkDetectionService $networkDetection, NmapService $nmapService): int
    {
        $context = $networkDetection->getDetectionContext();

        $this->info('=== ORION — Detection reseau ===');
        $this->newLine();

        if ($context['detected_interfaces'] === []) {
            $this->warn('Aucune interface IPv4 utilisable detectee.');
        } else {
            $this->table(
                ['Interface', 'IP', 'Masque', 'Subnet CIDR'],
                array_map(fn (array $iface) => [
                    $iface['name'],
                    $iface['ip'],
                    $iface['netmask'],
                    $iface['subnet'],
                ], $context['detected_interfaces'])
            );
        }

        $this->newLine();
        $this->line("Configure (.env) : ".($context['configured_subnet'] ?? '(vide — auto-detect active)'));
        $this->line("Subnet effectif   : {$context['effective_subnet']} (source: {$context['source']})");
        $this->newLine();
        $this->info($context['scan_prompt']);

        if ($this->option('scan')) {
            $resolved = $networkDetection->resolveDiscoverySubnet();
            $this->info("Lancement du scan Nmap sur {$resolved['subnet']}...");
            $result = $nmapService->discoverSubnet($resolved['subnet'], forceDirect: true);
            $this->info("Scan termine : {$result['hosts_found']} hote(s), {$result['devices_created']} nouveau(x), {$result['devices_updated']} mis a jour, {$result['devices_removed']} retire(s).");
            if (! empty($result['error'])) {
                $this->error($result['error']);
            } elseif (! empty($result['warning'])) {
                $this->warn($result['warning']);
            }
        } else {
            $this->comment('Pour scanner : php artisan orion:network-detect --scan');
            $this->comment('Ou : php artisan orion:discover');
        }

        return self::SUCCESS;
    }
}
