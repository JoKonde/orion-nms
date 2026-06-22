<?php

namespace App\Console\Commands;

use App\Services\Monitoring\NmapService;
use App\Services\Monitoring\NetworkDetectionService;
use Illuminate\Console\Command;

class DiscoverNetworkCommand extends Command
{
    protected $signature = 'orion:discover
                            {subnet? : Sous-reseau CIDR ex: 192.168.1.0/24}
                            {--json : Sortie JSON uniquement (pour sous-processus API)}
                            {--direct : Force Nmap direct sans delegation sous-processus}';

    protected $description = 'Lance une decouverte reseau Nmap sur le sous-reseau specifie';

    public function handle(NetworkDetectionService $networkDetection, NmapService $nmapService): int
    {
        $subnetArg = $this->argument('subnet');
        $resolved = $networkDetection->resolveDiscoverySubnet($subnetArg);
        $forceDirect = (bool) $this->option('direct');

        $result = $nmapService->discoverSubnet($resolved['subnet'], $forceDirect);

        if ($this->option('json')) {
            $this->line(json_encode($result, JSON_UNESCAPED_UNICODE));

            return ($result['success'] ?? false) ? self::SUCCESS : self::FAILURE;
        }

        $this->info($resolved['message']);
        $this->info("Decouverte Nmap en cours sur {$resolved['subnet']} (source: {$resolved['source']})...");
        $this->info("Scan termine : {$result['hosts_found']} hote(s), {$result['devices_created']} nouveau(x), {$result['devices_updated']} mis a jour, {$result['devices_removed']} retire(s).");

        if (! empty($result['error'])) {
            $this->error($result['error']);
        } elseif (! empty($result['warning'])) {
            $this->warn($result['warning']);
        }

        return ($result['success'] ?? false) ? self::SUCCESS : self::FAILURE;
    }
}
