<?php

namespace App\Listeners;

use App\Services\DashboardService;

/**
 * Vide le cache KPI dashboard quand le reseau change.
 */
class InvalidateDashboardCache
{
    public function __construct(private readonly DashboardService $dashboardService)
    {
    }

    public function handle(): void
    {
        $this->dashboardService->forgetCache();
    }
}
