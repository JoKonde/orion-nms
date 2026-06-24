<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\AiInsightType;
use App\Enums\PermissionName;
use App\Events\AiInsightCreated;
use App\Http\Controllers\Controller;
use App\Http\Requests\Ai\ChatRequest;
use App\Http\Resources\AiInsightResource;
use App\Models\AiInsight;
use App\Models\Alert;
use App\Models\Incident;
use App\Services\AiContextService;
use App\Services\OrionAiService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\RateLimiter;

class AiController extends Controller
{
    public function __construct(
        private readonly OrionAiService $aiService,
        private readonly AiContextService $contextService,
    ) {
    }

    /**
     * GET /api/v1/ai/status
     */
    public function status(): JsonResponse
    {
        $this->authorizeAi();

        return response()->json([
            'enabled' => $this->aiService->isEnabled(),
            'model' => config('orion.ai.model'),
        ]);
    }

    /**
     * POST /api/v1/ai/chat — niveau 1
     */
    public function chat(ChatRequest $request): JsonResponse
    {
        $this->authorizeAi();
        $this->throttleAi($request);

        $validated = $request->validated();
        $context = $this->contextService->buildNetworkSnapshot();

        $reply = $this->aiService->chatWithContext(
            $validated['message'],
            $context,
            $validated['history'] ?? null,
        );

        return response()->json([
            'reply' => $reply,
            'model' => config('orion.ai.model'),
        ]);
    }

    /**
     * POST /api/v1/ai/analyze/alert/{alert} — niveau 2
     */
    public function analyzeAlert(Request $request, Alert $alert): JsonResponse
    {
        $this->authorizeAi();
        $this->authorize('view', $alert);
        $this->throttleAi($request);

        $context = $this->contextService->buildAlertContext($alert);
        $content = $this->aiService->analyzeAlert($context);

        $insight = AiInsight::create([
            'type' => AiInsightType::ALERT_ANALYSIS,
            'title' => "Analyse alerte — {$alert->title}",
            'content' => $content,
            'user_id' => $request->user()->id,
            'alert_id' => $alert->id,
        ]);

        AiInsightCreated::dispatch($insight);

        return response()->json([
            'insight' => new AiInsightResource($insight),
        ]);
    }

    /**
     * POST /api/v1/ai/analyze/incident/{incident} — niveau 2
     */
    public function analyzeIncident(Request $request, Incident $incident): JsonResponse
    {
        $this->authorizeAi();
        $this->authorize('view', $incident);
        $this->throttleAi($request);

        $context = $this->contextService->buildIncidentContext($incident);
        $content = $this->aiService->analyzeIncident($context);

        $insight = AiInsight::create([
            'type' => AiInsightType::INCIDENT_ANALYSIS,
            'title' => "Analyse incident — {$incident->title}",
            'content' => $content,
            'user_id' => $request->user()->id,
            'incident_id' => $incident->id,
        ]);

        AiInsightCreated::dispatch($insight);

        return response()->json([
            'insight' => new AiInsightResource($insight),
        ]);
    }

    /**
     * GET /api/v1/ai/insights — historique + niveau 3 proactif
     */
    public function insights(Request $request): AnonymousResourceCollection
    {
        $this->authorizeAi();

        $limit = min((int) $request->get('limit', 20), 50);

        $insights = AiInsight::query()
            ->with('user:id,name')
            ->latest()
            ->limit($limit)
            ->get();

        return AiInsightResource::collection($insights);
    }

    private function authorizeAi(): void
    {
        abort_unless(
            request()->user()?->can(PermissionName::AI_USE->value),
            403,
            'Permission ORION AI requise.',
        );
    }

    private function throttleAi(Request $request): void
    {
        $key = 'ai:'.$request->user()->id;
        $max = (int) config('orion.ai.rate_limit_per_minute', 20);

        if (RateLimiter::tooManyAttempts($key, $max)) {
            abort(429, 'Trop de requetes ORION AI. Reessayez dans une minute.');
        }

        RateLimiter::hit($key, 60);
    }
}
