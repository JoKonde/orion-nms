<?php

namespace App\Enums;

enum ReportType: string
{
    case NETWORK_SUMMARY = 'network_summary';
    case DEVICES = 'devices';
    case AGENTS = 'agents';
    case ALERTS = 'alerts';
    case INCIDENTS = 'incidents';

    public function label(): string
    {
        return match ($this) {
            self::NETWORK_SUMMARY => 'Synthese reseau',
            self::DEVICES => 'Inventaire equipements',
            self::AGENTS => 'Inventaire agents',
            self::ALERTS => 'Historique alertes',
            self::INCIDENTS => 'Historique incidents',
        };
    }

    public function description(): string
    {
        return match ($this) {
            self::NETWORK_SUMMARY => 'KPIs, score de sante et etat global du parc.',
            self::DEVICES => 'Liste des equipements supervises.',
            self::AGENTS => 'Liste des agents ORION deployes.',
            self::ALERTS => 'Alertes levees sur une periode.',
            self::INCIDENTS => 'Incidents ouverts ou traites sur une periode.',
        };
    }

    public function supportsPeriod(): bool
    {
        return match ($this) {
            self::ALERTS, self::INCIDENTS => true,
            default => false,
        };
    }

    /**
     * @return array<int, string>
     */
    public static function values(): array
    {
        return array_map(fn (self $t) => $t->value, self::cases());
    }
}
