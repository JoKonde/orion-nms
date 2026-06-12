<?php

namespace App\Enums;

enum IncidentStatus: string
{
    case OPEN = 'open';
    case ASSIGNED = 'assigned';
    case IN_PROGRESS = 'in_progress';
    case RESOLVED = 'resolved';
    case CLOSED = 'closed';

    /**
     * Statuts consideres "ouverts" (incident actif).
     *
     * @return array<int, string>
     */
    public static function openValues(): array
    {
        return [
            self::OPEN->value,
            self::ASSIGNED->value,
            self::IN_PROGRESS->value,
        ];
    }

    /**
     * @return array<int, string>
     */
    public static function values(): array
    {
        return array_map(fn (self $s) => $s->value, self::cases());
    }
}
