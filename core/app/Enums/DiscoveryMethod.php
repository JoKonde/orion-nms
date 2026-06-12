<?php

namespace App\Enums;

/**
 * Methode par laquelle l'equipement a ete decouvert ou ajoute.
 *
 * Utile pour le dashboard et les rapports : on sait si un device a ete
 * ajoute manuellement, trouve par Nmap, ou remonte par un agent.
 */
enum DiscoveryMethod: string
{
    case MANUAL = 'manual';
    case PING = 'ping';
    case SNMP = 'snmp';
    case NMAP = 'nmap';
    case AGENT = 'agent';

    /**
     * @return array<int, string>
     */
    public static function values(): array
    {
        return array_map(fn (self $method) => $method->value, self::cases());
    }
}
