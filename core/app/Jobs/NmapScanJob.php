<?php

namespace App\Jobs;

use App\Services\Monitoring\NmapService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

/**
 * NmapScanJob — decouverte automatique des hotes sur un sous-reseau.
 *
 * Declenche par le Scheduler (quotidien) ou manuellement via :
 *   php artisan orion:discover 192.168.1.0/24
 *
 * Cree les nouveaux devices et emet DeviceDiscovered pour chaque nouvel hote.
 */
class NmapScanJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(public string $subnet)
    {
    }

    public function handle(NmapService $nmapService): void
    {
        $hosts = $nmapService->scanSubnet($this->subnet);
        $nmapService->persistDiscoveredHosts($hosts);
    }
}
