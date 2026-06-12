<?php

namespace App\Enums;

enum AlertStatus: string
{
    case RAISED = 'raised';
    case ACKNOWLEDGED = 'acknowledged';
    case RESOLVED = 'resolved';

    /**
     * Statuts consideres "actifs" (alerte non fermee).
     *
     * @return array<int, string>
     */
    public static function activeValues(): array
    {
        return [self::RAISED->value, self::ACKNOWLEDGED->value];
    }

    /**
     * @return array<int, string>
     */
    public static function values(): array
    {
        return array_map(fn (self $s) => $s->value, self::cases());
    }
}
