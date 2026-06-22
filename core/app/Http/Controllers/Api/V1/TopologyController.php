<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\PermissionName;
use App\Http\Controllers\Controller;
use App\Http\Resources\TopologyLinkResource;
use App\Services\TopologyService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * TopologyController — API cartographie reseau (format Cytoscape.js).
 */
class TopologyController extends Controller
{
    public function __construct(
        private readonly TopologyService $topologyService,
    ) {
    }

    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewTopology', \App\Models\Device::class);

        return response()->json($this->topologyService->getGraph());
    }

    /**
     * POST /api/v1/topology/rebuild
     *
     * Reconstruit les liens a partir des devices deja en base (pas de scan Nmap).
     */
    public function rebuild(Request $request): JsonResponse
    {
        $this->authorize('viewTopology', \App\Models\Device::class);

        if (! $request->user()?->can(PermissionName::TOPOLOGY_MANAGE->value)) {
            abort(403, 'Permission topology.manage requise.');
        }

        $topologyStats = $this->topologyService->rebuild();

        return response()->json([
            'message' => 'Topologie reconstruite.',
            'stats' => $topologyStats,
            'graph' => $this->topologyService->getGraph(),
        ]);
    }

    public function links(Request $request): JsonResponse
    {
        $this->authorize('viewTopology', \App\Models\Device::class);

        return response()->json([
            'data' => TopologyLinkResource::collection($this->topologyService->listLinks()),
        ]);
    }
}
