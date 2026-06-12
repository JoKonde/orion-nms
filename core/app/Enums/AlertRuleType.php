<?php

namespace App\Enums;

enum AlertRuleType: string
{
    case METRIC_THRESHOLD = 'metric_threshold';
    case DEVICE_OFFLINE = 'device_offline';

    /**
     * @return array<int, string>
     */
    public static function values(): array
    {
        return array_map(fn (self $t) => $t->value, self::cases());
    }
}
