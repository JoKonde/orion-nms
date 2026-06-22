<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;

/**
 * RealtimeController — config Reverb/Echo pour le dashboard React (Module 09).
 */
class RealtimeController extends Controller
{
    /**
     * GET /api/v1/realtime/config — parametres connexion Laravel Echo.
     */
    public function config(): JsonResponse
    {
        $reverb = config('broadcasting.connections.reverb');

        return response()->json([
            'driver' => config('broadcasting.default'),
            'enabled' => config('broadcasting.default') === 'reverb',
            'key' => $reverb['key'] ?? null,
            'host' => $reverb['options']['host'] ?? 'localhost',
            'port' => (int) ($reverb['options']['port'] ?? 8080),
            'scheme' => $reverb['options']['scheme'] ?? 'http',
            'auth_endpoint' => url('/broadcasting/auth'),
            'channels' => [
                'alerts' => 'org.alerts',
                'incidents' => 'org.incidents',
                'devices' => 'org.devices',
                'agents' => 'org.agents',
                'topology' => 'org.topology',
                'device_metrics' => 'device.{deviceId}.metrics',
            ],
            'events' => [
                'alert.raised',
                'incident.updated',
                'device.discovered',
                'agent.status.changed',
                'metric.received',
                'topology.updated',
            ],
        ]);
    }
}
