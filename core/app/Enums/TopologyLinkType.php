<?php

namespace App\Enums;

enum TopologyLinkType: string
{
    case LLDP = 'lldp';
    case NMAP_SUBNET = 'nmap_subnet';
    case MANUAL = 'manual';

    /**
     * @return array<int, string>
     */
    public static function values(): array
    {
        return array_map(fn (self $t) => $t->value, self::cases());
    }
}
