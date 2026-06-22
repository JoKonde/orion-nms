<?php

namespace App\Console\Commands;

use App\Services\TopologyService;
use Illuminate\Console\Command;

class RebuildTopologyCommand extends Command
{
    protected $signature = 'orion:topology-rebuild';

    protected $description = 'Reconstruit la cartographie reseau (liens subnet + LLDP SNMP)';

    public function handle(TopologyService $topology): int
    {
        $this->info('Reconstruction de la topologie ORION...');

        $result = $topology->rebuild();

        $this->table(
            ['Source', 'Liens crees/mis a jour'],
            [
                ['Subnet Nmap (/24)', $result['subnet_links']],
                ['LLDP SNMP', $result['lldp_links']],
                ['Liens subnet obsoletes supprimes', $result['stale_subnet_links_removed']],
            ]
        );

        $graph = $topology->getGraph();
        $this->info("Graphe : {$graph['meta']['node_count']} noeuds, {$graph['meta']['edge_count']} liens.");

        return self::SUCCESS;
    }
}
