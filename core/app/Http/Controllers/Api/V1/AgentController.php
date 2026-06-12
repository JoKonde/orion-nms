<?php

namespace App\Http\Controllers\Api\V1;

use App\Data\AgentRegisterData;
use App\Data\HeartbeatData;
use App\Http\Controllers\Controller;
use App\Http\Requests\Agent\HeartbeatRequest;
use App\Http\Requests\Agent\RegisterAgentRequest;
use App\Http\Resources\AgentResource;
use App\Models\Agent;
use App\Services\AgentHeartbeatService;
use App\Services\AgentRegistrationService;
use App\Services\AgentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class AgentController extends Controller
{
    public function __construct(
        private readonly AgentRegistrationService $registrationService,
        private readonly AgentHeartbeatService $heartbeatService,
        private readonly AgentService $agentService,
    ) {
    }

    /**
     * POST /api/v1/agents/register
     * Header requis : X-Orion-Bootstrap-Key
     */
    public function register(RegisterAgentRequest $request): JsonResponse
    {
        $bootstrapKey = $request->header('X-Orion-Bootstrap-Key', '');

        $result = $this->registrationService->register(
            AgentRegisterData::from($request->validated()),
            $bootstrapKey
        );

        return response()->json([
            'message' => 'Agent enregistre avec succes.',
            'agent' => new AgentResource($result['agent']),
            // ATTENTION : la cle API n'est retournee qu'une seule fois. L'agent doit la stocker localement.
            'api_key' => $result['api_key'],
        ], 201);
    }

    /**
     * POST /api/v1/agents/heartbeat
     * Middleware : agent.api (cle API + UUID agent)
     */
    public function heartbeat(HeartbeatRequest $request): AgentResource
    {
        /** @var Agent $agent */
        $agent = $request->attributes->get('agent');

        $agent = $this->heartbeatService->record(
            $agent,
            HeartbeatData::from($request->validated())
        );

        return new AgentResource($agent);
    }

    /**
     * GET /api/v1/agents — liste pour le dashboard admin
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $this->authorize('viewAny', Agent::class);

        $agents = $this->agentService->paginate(
            filters: $request->only(['status', 'search']),
            perPage: (int) $request->get('per_page', 15),
        );

        return AgentResource::collection($agents);
    }

    /**
     * GET /api/v1/agents/{agent}
     */
    public function show(Agent $agent): AgentResource
    {
        $this->authorize('view', $agent);

        return new AgentResource($agent->load('device'));
    }

    /**
     * DELETE /api/v1/agents/{agent}
     */
    public function destroy(Agent $agent): JsonResponse
    {
        $this->authorize('delete', $agent);

        // Supprimer le device cascade aussi l'agent (FK agents.device_id).
        $agent->device?->delete();

        return response()->json(['message' => 'Agent supprime.']);
    }
}
