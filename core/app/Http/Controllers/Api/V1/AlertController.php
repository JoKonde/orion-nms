<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\AlertResource;
use App\Models\Alert;
use App\Services\AlertService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class AlertController extends Controller
{
    public function __construct(private readonly AlertService $alertService)
    {
    }

    public function index(Request $request): AnonymousResourceCollection
    {
        $this->authorize('viewAny', Alert::class);

        $alerts = $this->alertService->paginate(
            filters: $request->only(['status', 'severity', 'device_id']),
            perPage: (int) $request->get('per_page', 15),
        );

        return AlertResource::collection($alerts);
    }

    public function show(Alert $alert): AlertResource
    {
        $this->authorize('view', $alert);

        return new AlertResource($alert->load(['device', 'rule', 'acknowledgedByUser', 'resolvedByUser']));
    }

    public function acknowledge(Alert $alert): AlertResource
    {
        $this->authorize('manage', $alert);

        $updated = $this->alertService->acknowledge($alert, request()->user());

        return new AlertResource($updated);
    }

    public function resolve(Alert $alert): AlertResource
    {
        $this->authorize('manage', $alert);

        $updated = $this->alertService->resolve($alert, request()->user());

        return new AlertResource($updated);
    }
}
