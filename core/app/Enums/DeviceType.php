<?php

namespace App\Enums;

/**
 * Types d'equipements supervises par ORION NMS.
 *
 * Chaque equipement du parc reseau appartient a un type precis.
 * L'enum evite les fautes de frappe ("routre" au lieu de "router")
 * et permet d'adapter la supervision (SNMP, ping, agent) selon le type.
 */
enum DeviceType: string
{
    case ROUTER = 'router';
    case SWITCH = 'switch';
    case FIREWALL = 'firewall';
    case PRINTER = 'printer';
    case ACCESS_POINT = 'access_point';
    case PC = 'pc';
    case OTHER = 'other';

    /**
     * @return array<int, string>
     */
    public static function values(): array
    {
        return array_map(fn (self $type) => $type->value, self::cases());
    }
}
