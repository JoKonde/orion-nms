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

    /*
    |--------------------------------------------------------------------------
    | Monitoring reseau sans agent (Module 05)
    |--------------------------------------------------------------------------
    */
    'monitoring' => [
        'snmp_community' => env('ORION_SNMP_COMMUNITY', 'public'),
        'snmp_timeout' => (int) env('ORION_SNMP_TIMEOUT', 3),
        /*
        | Si vide : auto-detection au runtime (NetworkDetectionService).
        | Sinon : valeur fixe pour le scheduler Nmap et les scans par defaut.
        */
        'default_subnet' => env('ORION_DISCOVERY_SUBNET'),
        'ping_timeout_ms' => (int) env('ORION_PING_TIMEOUT_MS', 2000),
        // Chemin complet vers nmap (Windows : php artisan serve n'a pas toujours le PATH).
        'nmap_binary' => env('ORION_NMAP_BINARY'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Dashboard API (Module 12)
    |--------------------------------------------------------------------------
    */
    'dashboard' => [
        // TTL cache Redis en secondes pour overview / health.
        'cache_ttl' => (int) env('ORION_DASHBOARD_CACHE_TTL', 60),
    ],

];
