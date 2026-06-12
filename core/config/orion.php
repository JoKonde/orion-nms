<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Cle bootstrap agent (auto-enregistrement)
    |--------------------------------------------------------------------------
    |
    | L'agent ORION doit presenter cette cle lors du premier enregistrement
    | (POST /api/v1/agents/register). Elle evite qu'un inconnu enregistre
    | des agents sur ton serveur. A changer en production (.env).
    |
    */
    'agent' => [
        'bootstrap_key' => env('ORION_AGENT_BOOTSTRAP_KEY', 'orion-bootstrap-change-me'),

        /*
        | Delai en secondes sans heartbeat avant de marquer l'agent "offline".
        | Le Job CheckAgentsOfflineJob compare last_seen_at a ce seuil.
        */
        'heartbeat_timeout' => (int) env('ORION_AGENT_HEARTBEAT_TIMEOUT', 120),
    ],

];
