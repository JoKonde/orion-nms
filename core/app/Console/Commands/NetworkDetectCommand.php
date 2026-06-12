<?php

namespace App\Console\Commands;

use App\Jobs\NmapScanJob;
use App\Services\Monitoring\NetworkDetectionService;
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

    public function handle(NetworkDetectionService $networkDetection): int
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
            NmapScanJob::dispatchSync($resolved['subnet']);
            $this->info('Scan termine.');
        } else {
            $this->comment('Pour scanner : php artisan orion:network-detect --scan');
            $this->comment('Ou : php artisan orion:discover');
        }

        return self::SUCCESS;
    }
}
