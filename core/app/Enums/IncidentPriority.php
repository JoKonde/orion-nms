<?php

namespace App\Enums;

enum IncidentPriority: string
{
    case LOW = 'low';
    case MEDIUM = 'medium';
    case HIGH = 'high';
    case CRITICAL = 'critical';

    /**
     * @return array<int, string>
     */
    public static function values(): array
    {
        return array_map(fn (self $p) => $p->value, self::cases());
    }

    /**
     * Convertit la severite d'une alerte en priorite incident.
     */
    public static function fromAlertSeverity(AlertSeverity $severity): self
    {
        return match ($severity) {
            AlertSeverity::CRITICAL => self::CRITICAL,
            AlertSeverity::WARNING => self::HIGH,
            AlertSeverity::INFO => self::MEDIUM,
        };
    }
}
