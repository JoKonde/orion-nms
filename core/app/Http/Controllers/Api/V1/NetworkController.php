<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Device\DiscoverNetworkRequest;
use App\Jobs\NmapScanJob;
use App\Services\Monitoring\NetworkDetectionService;
use Illuminate\Http\JsonResponse;

/**
 * NetworkController — detection reseau locale + lancement scan Nmap.
 *
 * Utilise par le dashboard React (plus tard) pour :
 *   - Afficher le reseau detecte et proposer le scan
 *   - Permettre la saisie manuelle du subnet (avec aide ipconfig / ip addr)
 */
class NetworkController extends Controller
{
    public function __construct(private readonly NetworkDetectionService $networkDetection)
    {
    }

    /**
     * GET /api/v1/network/detected
     *
     * Retourne interfaces detectees, subnet effectif et texte d'aide pour l'admin.
     */
    public function detected(): JsonResponse
    {
        return response()->json($this->networkDetection->getDetectionContext());
    }

    /**
     * POST /api/v1/network/discover
     *
     * Lance un scan Nmap sur le subnet fourni, ou sur le subnet effectif (.env ou auto-detect).
     */
    public function discover(DiscoverNetworkRequest $request): JsonResponse
    {
        $subnetInput = $request->validated()['subnet'] ?? null;
        $resolved = $this->networkDetection->resolveDiscoverySubnet($subnetInput);

        NmapScanJob::dispatch($resolved['subnet']);

        return response()->json([
            'message' => "Réseau détecté : {$resolved['subnet']} — scan planifié.",
            'detail' => $resolved['message'],
            'subnet' => $resolved['subnet'],
            'source' => $resolved['source'],
        ], 202);
    }
}
