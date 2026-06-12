<?php

namespace App\Http\Controllers\Api\V1;

use App\Data\IncidentData;
use App\Http\Controllers\Controller;
use App\Http\Requests\Incident\AssignIncidentRequest;
use App\Http\Requests\Incident\ResolveIncidentRequest;
use App\Http\Requests\Incident\StoreIncidentRequest;
use App\Http\Requests\Incident\UpdateIncidentRequest;
use App\Http\Resources\IncidentResource;
use App\Models\Alert;
use App\Models\Incident;
use App\Models\User;
use App\Services\IncidentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class IncidentController extends Controller
{
    public function __construct(private readonly IncidentService $incidentService)
    {
    }

    public function index(Request $request): AnonymousResourceCollection
    {
        $this->authorize('viewAny', Incident::class);

        $incidents = $this->incidentService->paginate(
            filters: $request->only(['status', 'priority', 'assigned_to', 'device_id']),
            perPage: (int) $request->get('per_page', 15),
        );

        return IncidentResource::collection($incidents);
    }

    public function store(StoreIncidentRequest $request): JsonResponse
    {
        $this->authorize('create', Incident::class);

        $incident = $this->incidentService->create(
            IncidentData::from($request->validated()),
            $request->user(),
        );

        return (new IncidentResource($incident))
            ->response()
            ->setStatusCode(201);
    }

    public function show(Incident $incident): IncidentResource
    {
        $this->authorize('view', $incident);

        return new IncidentResource($incident->load([
            'device', 'alert', 'creator', 'assignee', 'resolver', 'closer',
        ]));
    }

    public function update(UpdateIncidentRequest $request, Incident $incident): IncidentResource
    {
        $this->authorize('update', $incident);

        $updated = $this->incidentService->update($incident, $request->validated());

        return new IncidentResource($updated);
    }

    public function destroy(Incident $incident): JsonResponse
    {
        $this->authorize('delete', $incident);

        $this->incidentService->delete($incident);

        return response()->json(['message' => 'Incident supprime.']);
    }

    public function assign(AssignIncidentRequest $request, Incident $incident): IncidentResource
    {
        $this->authorize('assign', $incident);

        $assignee = User::findOrFail($request->validated()['assigned_to']);
        $updated = $this->incidentService->assign($incident, $assignee, $request->user());

        return new IncidentResource($updated);
    }

    public function start(Incident $incident): IncidentResource
    {
        $this->authorize('update', $incident);

        $updated = $this->incidentService->start($incident, request()->user());

        return new IncidentResource($updated);
    }

    public function resolve(ResolveIncidentRequest $request, Incident $incident): IncidentResource
    {
        $this->authorize('update', $incident);

        $updated = $this->incidentService->resolve(
            $incident,
            $request->user(),
            $request->validated()['resolution_notes'] ?? null,
        );

        return new IncidentResource($updated);
    }

    public function close(Incident $incident): IncidentResource
    {
        $this->authorize('close', $incident);

        $updated = $this->incidentService->close($incident, request()->user());

        return new IncidentResource($updated);
    }

    /**
     * POST /api/v1/alerts/{alert}/escalate — escalade manuelle alerte → incident.
     */
    public function escalateFromAlert(Alert $alert): JsonResponse
    {
        $this->authorize('create', Incident::class);

        $incident = $this->incidentService->createFromAlert($alert, request()->user());

        return (new IncidentResource($incident->load(['device', 'alert', 'creator'])))
            ->response()
            ->setStatusCode(201);
    }
}
