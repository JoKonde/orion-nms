<?php

namespace App\Enums;

enum AiInsightType: string
{
    case PROACTIVE = 'proactive';
    case ALERT_ANALYSIS = 'alert_analysis';
    case INCIDENT_ANALYSIS = 'incident_analysis';

    /**
     * @return array<int, string>
     */
    public static function values(): array
    {
        return array_map(fn (self $t) => $t->value, self::cases());
    }
}
