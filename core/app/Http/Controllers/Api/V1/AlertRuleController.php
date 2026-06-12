<?php

namespace App\Http\Controllers\Api\V1;

use App\Data\AlertRuleData;
use App\Http\Controllers\Controller;
use App\Http\Requests\AlertRule\StoreAlertRuleRequest;
use App\Http\Requests\AlertRule\UpdateAlertRuleRequest;
use App\Http\Resources\AlertRuleResource;
use App\Models\AlertRule;
use App\Services\AlertRuleService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class AlertRuleController extends Controller
{
    public function __construct(private readonly AlertRuleService $alertRuleService)
    {
    }

    public function index(Request $request): AnonymousResourceCollection
    {
        $this->authorize('viewAny', AlertRule::class);

        $rules = $this->alertRuleService->paginate(
            filters: $request->only(['rule_type', 'is_enabled', 'device_id']),
            perPage: (int) $request->get('per_page', 15),
        );

        return AlertRuleResource::collection($rules);
    }

    public function store(StoreAlertRuleRequest $request): JsonResponse
    {
        $this->authorize('create', AlertRule::class);

        $rule = $this->alertRuleService->create(AlertRuleData::from($request->validated()));

        return (new AlertRuleResource($rule))
            ->response()
            ->setStatusCode(201);
    }

    public function show(AlertRule $alertRule): AlertRuleResource
    {
        $this->authorize('view', $alertRule);

        return new AlertRuleResource($alertRule->load('device'));
    }

    public function update(UpdateAlertRuleRequest $request, AlertRule $alertRule): AlertRuleResource
    {
        $this->authorize('update', $alertRule);

        $merged = [
            'name' => $alertRule->name,
            'description' => $alertRule->description,
            'rule_type' => $alertRule->rule_type->value,
            'metric_type' => $alertRule->metric_type?->value,
            'operator' => $alertRule->operator?->value,
            'threshold' => $alertRule->threshold,
            'severity' => $alertRule->severity->value,
            'device_id' => $alertRule->device_id,
            'is_enabled' => $alertRule->is_enabled,
            'cooldown_minutes' => $alertRule->cooldown_minutes,
        ] + $request->validated();

        $rule = $this->alertRuleService->update($alertRule, AlertRuleData::from($merged));

        return new AlertRuleResource($rule);
    }

    public function destroy(AlertRule $alertRule): JsonResponse
    {
        $this->authorize('delete', $alertRule);

        $this->alertRuleService->delete($alertRule);

        return response()->json(['message' => 'Regle d\'alerte supprimee.']);
    }
}
