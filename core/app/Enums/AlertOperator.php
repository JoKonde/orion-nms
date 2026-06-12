<?php

namespace App\Enums;

enum AlertOperator: string
{
    case GT = 'gt';
    case GTE = 'gte';
    case LT = 'lt';
    case LTE = 'lte';

    /**
     * @return array<int, string>
     */
    public static function values(): array
    {
        return array_map(fn (self $o) => $o->value, self::cases());
    }
}
