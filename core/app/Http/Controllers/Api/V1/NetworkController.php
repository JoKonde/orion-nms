<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Device\DiscoverNetworkRequest;
use App\Jobs\NmapScanJob;
use App\Services\Monitoring\NetworkDetectionService;
use App\Support\TextEncoding;
use Illuminate\Http\JsonResponse;

class NetworkController extends Controller
{
    public function __construct(private readonly NetworkDetectionService $networkDetection)
    {
    }

    public function detected(): JsonResponse
    {
        return response()->json($this->networkDetection->getDetectionContext());
    }

    public function discover(DiscoverNetworkRequest $request): JsonResponse
    {
        $subnetInput = $request->validated()['subnet'] ?? null;
        $resolved = $this->networkDetection->resolveDiscoverySubnet($subnetInput);
        $scan = NmapScanJob::runSync($resolved['subnet']);
        $payload = self::formatScanResponse($resolved, $scan);
        $status = $payload['success'] ? 200 : 503;
        unset($payload['success']);

        return response()->json($payload, $status);
    }

    /**
     * @param  array{subnet: string, source: string, message: string}  $resolved
     * @param  array{success: bool, hosts_found: int, devices_created: int, devices_updated: int, nmap_binary: ?string, error: ?string, warning: ?string}  $scan
     * @return array<string, mixed>
     */
    public static function formatScanResponse(array $resolved, array $scan): array
    {
        $message = $scan['success']
            ? "Scan terminé sur {$resolved['subnet']}."
            : ($scan['error'] ?? 'Echec du scan reseau.');

        return TextEncoding::sanitizeArray([
            'success' => $scan['success'],
            'message' => $message,
            'detail' => $resolved['message'],
            'subnet' => $resolved['subnet'],
            'source' => $resolved['source'],
            'nmap_available' => $scan['nmap_binary'] !== null,
            'nmap_binary' => $scan['nmap_binary'],
            'hosts_found' => $scan['hosts_found'],
            'devices_created' => $scan['devices_created'],
            'devices_updated' => $scan['devices_updated'] ?? 0,
            'devices_removed' => $scan['devices_removed'] ?? 0,
            'warning' => $scan['warning'],
            'error' => $scan['error'],
        ]);
    }
}
