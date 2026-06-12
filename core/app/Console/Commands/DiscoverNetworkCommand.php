<?php

namespace App\Console\Commands;

use App\Jobs\NmapScanJob;
use App\Services\Monitoring\NetworkDetectionService;
use Illuminate\Console\Command;

class DiscoverNetworkCommand extends Command
{
    protected $signature = 'orion:discover {subnet? : Sous-reseau CIDR ex: 192.168.1.0/24}';

    protected $description = 'Lance une decouverte reseau Nmap sur le sous-reseau specifie';

    public function handle(NetworkDetectionService $networkDetection): int
    {
        $subnetArg = $this->argument('subnet');
        $resolved = $networkDetection->resolveDiscoverySubnet($subnetArg);

        $this->info($resolved['message']);
        $this->info("Decouverte Nmap en cours sur {$resolved['subnet']} (source: {$resolved['source']})...");

        NmapScanJob::dispatchSync($resolved['subnet']);

        $this->info('Scan termine. Consultez les devices via API ou base de donnees.');

        return self::SUCCESS;
    }
}
