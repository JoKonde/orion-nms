<?php

namespace App\Http\Middleware;

use App\Models\Agent;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Symfony\Component\HttpFoundation\Response;

/**
 * AgentApiKeyAuth — authentifie les requetes venant d'un ORION Agent.
 *
 * L'agent envoie :
 *   - Header Authorization: Bearer {api_key}
 *   - Body ou header X-Agent-UUID: {agent_uuid}
 *
 * On retrouve l'agent par UUID puis on verifie la cle avec Hash::check
 * (comparaison securisee avec le hash stocke en base).
 *
 * Difference avec Sanctum : les agents n'ont pas de compte utilisateur,
 * ils ont leur propre cle API dediee.
 */
class AgentApiKeyAuth
{
    public function handle(Request $request, Closure $next): Response
    {
        $agentUuid = $request->header('X-Agent-UUID') ?? $request->input('agent_uuid');

        // bearerToken() lit Authorization: Bearer {cle}
        // Fallback X-Agent-Api-Key : necessaire avec "php artisan serve" qui ne transmet
        // pas toujours le header Authorization au serveur PHP integre.
        $apiKey = $request->bearerToken() ?? $request->header('X-Agent-Api-Key');

        if (! $agentUuid || ! $apiKey) {
            return response()->json([
                'message' => 'UUID agent et cle API requis.',
            ], 401);
        }

        $agent = Agent::where('agent_uuid', $agentUuid)->first();

        if (! $agent || ! Hash::check($apiKey, $agent->api_key_hash)) {
            return response()->json([
                'message' => 'Authentification agent invalide.',
            ], 401);
        }

        // Injecte l'agent dans la requete pour le controller.
        $request->attributes->set('agent', $agent);

        return $next($request);
    }
}
