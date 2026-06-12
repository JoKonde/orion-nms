<?php

namespace App\Enums;

/**
 * Statut de connexion d'un agent ORION au Core.
 *
 * Mis a jour a chaque heartbeat (online) ou par CheckAgentsOfflineJob (offline).
 */
enum AgentStatus: string
{
    case ONLINE = 'online';
    case OFFLINE = 'offline';

    /**
     * @return array<int, string>
     */
    public static function values(): array
    {
        return array_map(fn (self $status) => $status->value, self::cases());
    }
}
