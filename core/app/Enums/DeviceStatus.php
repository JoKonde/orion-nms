<?php

namespace App\Enums;

/**
 * Statut de disponibilite d'un equipement.
 *
 * Mis a jour par le monitoring (Module 05 : ping/SNMP) ou par le heartbeat agent.
 * "unknown" = equipement enregistre mais pas encore sonde.
 */
enum DeviceStatus: string
{
    case ONLINE = 'online';
    case OFFLINE = 'offline';
    case UNKNOWN = 'unknown';

    /**
     * @return array<int, string>
     */
    public static function values(): array
    {
        return array_map(fn (self $status) => $status->value, self::cases());
    }
}
