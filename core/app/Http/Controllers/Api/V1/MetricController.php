<?php

namespace App\Http\Controllers\Api\V1;

use App\Data\MetricBatchData;
use App\Data\MetricPointData;
use App\Http\Controllers\Controller;
use App\Http\Requests\Metric\StoreMetricsRequest;
use App\Http\Resources\MetricResource;
use App\Models\Agent;
use App\Models\Device;
use App\Services\MetricIngestionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class MetricController extends Controller
{
    public function __construct(private readonly MetricIngestionService $metricService)
    {
    }

    /**
     * POST /api/v1/agents/metrics — ingestion bulk par l'agent (middleware agent.api).
     */
    public function store(StoreMetricsRequest $request): JsonResponse
    {
        /** @var Agent $agent */
        $agent = $request->attributes->get('agent');

        $validated = $request->validated();

        $batch = array_map(
            fn (array $item) => MetricPointData::from($item),
            $validated['batch']
        );

        $batchData = new MetricBatchData(
            agent_uuid: $validated['agent_uuid'],
            batch: $batch,
        );

        $count = $this->metricService->ingest($agent, $batchData);

        return response()->json([
            'message' => 'Metriques enregistrees.',
            'inserted' => $count,
        ], 201);
    }

    /**
     * GET /api/v1/devices/{device}/metrics
     *
     * Query : type=cpu&from=2026-06-01&to=2026-06-12&granularity=raw|hourly
     */
    public function index(Request $request, Device $device): AnonymousResourceCollection
    {
        $this->authorize('view', $device);

        $granularity = $request->get('granularity', 'raw');
        $type = $request->get('type');
        $from = $request->get('from');
        $to = $request->get('to');

        if ($granularity === 'hourly') {
            $metrics = $this->metricService->queryHourly($device->id, $type, $from, $to);
        } else {
            $limit = min((int) $request->get('limit', 1000), 5000);
            $metrics = $this->metricService->queryRaw($device->id, $type, $from, $to, $limit);
        }

        return MetricResource::collection($metrics);
    }
}
