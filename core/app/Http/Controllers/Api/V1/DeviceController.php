<?php

namespace App\Http\Controllers\Api\V1;

use App\Data\DeviceData;
use App\Http\Controllers\Controller;
use App\Http\Requests\Device\StoreDeviceRequest;
use App\Http\Requests\Device\UpdateDeviceRequest;
use App\Http\Resources\DeviceResource;
use App\Models\Device;
use App\Services\DeviceService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

/**
 * DeviceController — CRUD equipements reseau (Module 02).
 *
 * Demonstration du flux complet ORION :
 *   Request (validation) -> DeviceData (DTO) -> DeviceService -> DeviceResource (JSON)
 */
class DeviceController extends Controller
{
    public function __construct(private readonly DeviceService $deviceService)
    {
    }

    /**
     * GET /api/v1/devices?type=router&status=online&search=cisco
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $this->authorize('viewAny', Device::class);

        $devices = $this->deviceService->paginate(
            filters: $request->only(['type', 'status', 'search']),
            perPage: (int) $request->get('per_page', 15),
        );

        return DeviceResource::collection($devices);
    }

    /**
     * POST /api/v1/devices
     */
    public function store(StoreDeviceRequest $request): JsonResponse
    {
        // DeviceData::from() : conversion du tableau valide en DTO type (Spatie Laravel Data).
        $device = $this->deviceService->create(DeviceData::from($request->validated()));

        return (new DeviceResource($device))
            ->response()
            ->setStatusCode(201);
    }

    /**
     * GET /api/v1/devices/{device}
     */
    public function show(Device $device): DeviceResource
    {
        $this->authorize('view', $device);

        return new DeviceResource($device);
    }

    /**
     * PUT/PATCH /api/v1/devices/{device}
     */
    public function update(UpdateDeviceRequest $request, Device $device): DeviceResource
    {
        $device = $this->deviceService->update($device, $request->validated());

        return new DeviceResource($device);
    }

    /**
     * DELETE /api/v1/devices/{device}
     */
    public function destroy(Device $device): JsonResponse
    {
        $this->authorize('delete', $device);

        $this->deviceService->delete($device);

        return response()->json(['message' => 'Equipement supprime.']);
    }
}
