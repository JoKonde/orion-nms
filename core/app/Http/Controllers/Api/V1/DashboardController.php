<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Services\DashboardService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * DashboardController — vue globale ORION (Module 12).
 *
 * Endpoints consommes par la page d'accueil du dashboard React.
 */
class DashboardController extends Controller
{
    public function __construct(private readonly DashboardService $dashboardService)
    {
    }

    /**
     * GET /api/v1/dashboard/overview — resume reseau (devices, agents, alertes...).
     */
    public function overview(Request $request): JsonResponse
    {
        if ($request->boolean('fresh')) {
            $this->dashboardService->forgetCache();
        }

        return response()->json($this->dashboardService->getOverview());
    }

    /**
     * GET /api/v1/dashboard/health — score de sante detaille.
     */
    public function health(Request $request): JsonResponse
    {
        if ($request->boolean('fresh')) {
            $this->dashboardService->forgetCache();
        }

        return response()->json($this->dashboardService->getHealth());
    }
}
