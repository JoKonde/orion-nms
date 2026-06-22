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
 */
class NmapScanJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(public string $subnet)
    {
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
    public static function runSync(string $subnet): array
    {
        return (new self($subnet))->handle(app(NmapService::class));
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
    public function handle(NmapService $nmapService): array
    {
        return $nmapService->discoverSubnet($this->subnet);
    }
}
