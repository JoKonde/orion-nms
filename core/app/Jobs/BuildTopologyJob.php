<?php

namespace App\Jobs;

use App\Services\TopologyService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * BuildTopologyJob — reconstruit les liens reseau (subnet + LLDP).
 *
 * Planifie apres le scan Nmap quotidien pour enrichir la cartographie.
 */
class BuildTopologyJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function handle(TopologyService $topology): void
    {
        $result = $topology->rebuild();

        Log::info('ORION topology rebuilt', $result);
    }
}
