<?php

namespace App\Enums;

/**
 * Types de metriques collectees par ORION (agent ou SNMP).
 *
 * Convention : nom court en snake_case, stocke tel quel en base.
 * Extensible : ajouter de nouveaux types sans migration (colonne metric_type string).
 */
enum MetricType: string
{
    case CPU = 'cpu';
    case RAM = 'ram';
    case RAM_TOTAL = 'ram_total';
    case SWAP_USAGE = 'swap_usage';
    case DISK = 'disk';
    case DISK_USAGE = 'disk_usage';
    case TEMPERATURE = 'temperature';
    case NETWORK_IN = 'network_in';
    case NETWORK_OUT = 'network_out';
    case UPTIME = 'uptime';

    /**
     * @return array<int, string>
     */
    public static function values(): array
    {
        return array_map(fn (self $type) => $type->value, self::cases());
    }
}
