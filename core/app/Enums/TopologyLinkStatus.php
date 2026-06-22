<?php

namespace App\Enums;

enum TopologyLinkStatus: string
{
    case UP = 'up';
    case DOWN = 'down';
    case UNKNOWN = 'unknown';

    /**
     * @return array<int, string>
     */
    public static function values(): array
    {
        return array_map(fn (self $s) => $s->value, self::cases());
    }
}
