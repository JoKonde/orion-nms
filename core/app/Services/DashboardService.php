<?php

namespace App\Services;

use App\Data\DashboardHealthData;
use App\Data\DashboardOverviewData;
use App\Enums\AgentStatus;
use App\Enums\AlertSeverity;
use App\Enums\AlertStatus;
use App\Enums\DeviceStatus;
use App\Enums\IncidentStatus;
use App\Models\Agent;
use App\Models\Alert;
use App\Models\Device;
use App\Models\Incident;
use App\Models\TopologyLink;
use Illuminate\Support\Facades\Cache;

/**
 * DashboardService — agregation des KPIs reseau pour le tableau de bord ORION.
 *
 * POURQUOI un service dedie + cache Redis ?
 * -----------------------------------------
 * La home page du dashboard React appelle un seul endpoint au lieu de 5+ APIs.
 * Le cache TTL court (60s par defaut) evite de recalculer les compteurs a chaque refresh.
 */
class DashboardService
{
    private const CACHE_OVERVIEW = 'orion.dashboard.overview';

    private const CACHE_HEALTH = 'orion.dashboard.health';

    public function getOverview(): DashboardOverviewData
    {
        return Cache::remember(
            self::CACHE_OVERVIEW,
            $this->cacheTtl(),
            fn () => $this->buildOverview(),
        );
    }

    public function getHealth(): DashboardHealthData
    {
        return Cache::remember(
            self::CACHE_HEALTH,
            $this->cacheTtl(),
            fn () => $this->buildHealth(),
        );
    }

    /**
     * Invalide le cache dashboard (utile apres tests ou Module 09 broadcast).
     */
    public function forgetCache(): void
    {
        Cache::forget(self::CACHE_OVERVIEW);
        Cache::forget(self::CACHE_HEALTH);
    }

    private function buildOverview(): DashboardOverviewData
    {
        $stats = $this->collectStats();
        $health = $this->computeHealth($stats);

        return DashboardOverviewData::from([
            'devices' => [
                'total' => $stats['devices_total'],
                'online' => $stats['devices_online'],
                'offline' => $stats['devices_offline'],
                'unknown' => $stats['devices_unknown'],
            ],
            'agents' => [
                'total' => $stats['agents_total'],
                'online' => $stats['agents_online'],
                'offline' => $stats['agents_offline'],
            ],
            'alerts' => [
                'active' => $stats['alerts_active'],
                'critical' => $stats['alerts_critical'],
                'warning' => $stats['alerts_warning'],
                'info' => $stats['alerts_info'],
            ],
            'incidents' => [
                'open' => $stats['incidents_open'],
                'critical_priority' => $stats['incidents_critical'],
            ],
            'topology' => [
                'links' => $stats['topology_links'],
            ],
            'health' => [
                'score' => $health['score'],
                'grade' => $health['grade'],
                'factors' => $health['factors'],
            ],
            'generated_at' => now()->toIso8601String(),
        ]);
    }

    private function buildHealth(): DashboardHealthData
    {
        $stats = $this->collectStats();
        $health = $this->computeHealth($stats);

        return DashboardHealthData::from([
            'score' => $health['score'],
            'grade' => $health['grade'],
            'factors' => $health['factors'],
            'generated_at' => now()->toIso8601String(),
        ]);
    }

    /**
     * @return array<string, int>
     */
    private function collectStats(): array
    {
        return [
            'devices_total' => Device::count(),
            'devices_online' => Device::where('status', DeviceStatus::ONLINE)->count(),
            'devices_offline' => Device::where('status', DeviceStatus::OFFLINE)->count(),
            'devices_unknown' => Device::where('status', DeviceStatus::UNKNOWN)->count(),
            'agents_total' => Agent::count(),
            'agents_online' => Agent::where('status', AgentStatus::ONLINE)->count(),
            'agents_offline' => Agent::where('status', AgentStatus::OFFLINE)->count(),
            'alerts_active' => Alert::whereIn('status', AlertStatus::activeValues())->count(),
            'alerts_critical' => Alert::whereIn('status', AlertStatus::activeValues())
                ->where('severity', AlertSeverity::CRITICAL)->count(),
            'alerts_warning' => Alert::whereIn('status', AlertStatus::activeValues())
                ->where('severity', AlertSeverity::WARNING)->count(),
            'alerts_info' => Alert::whereIn('status', AlertStatus::activeValues())
                ->where('severity', AlertSeverity::INFO)->count(),
            'incidents_open' => Incident::whereIn('status', IncidentStatus::openValues())->count(),
            'incidents_critical' => Incident::whereIn('status', IncidentStatus::openValues())
                ->where('priority', 'critical')->count(),
            'topology_links' => TopologyLink::count(),
        ];
    }

    /**
     * Score 0-100 : disponibilite devices/agents moins penalites alertes/incidents.
     *
     * @param  array<string, int>  $stats
     * @return array{score: int, grade: string, factors: array<string, mixed>}
     */
    private function computeHealth(array $stats): array
    {
        $deviceAvailability = $this->percentage(
            $stats['devices_online'],
            max(1, $stats['devices_total'] - $stats['devices_unknown']),
        );

        $agentAvailability = $stats['agents_total'] > 0
            ? $this->percentage($stats['agents_online'], $stats['agents_total'])
            : 100;

        $alertPenalty = min(35, ($stats['alerts_critical'] * 12) + ($stats['alerts_warning'] * 4));
        $incidentPenalty = min(25, ($stats['incidents_critical'] * 10) + ($stats['incidents_open'] * 3));

        $rawScore = ($deviceAvailability * 0.55) + ($agentAvailability * 0.15) - $alertPenalty - $incidentPenalty;
        $score = (int) max(0, min(100, round($rawScore)));

        return [
            'score' => $score,
            'grade' => $this->healthGrade($score),
            'factors' => [
                'device_availability_pct' => $deviceAvailability,
                'agent_availability_pct' => $agentAvailability,
                'alert_penalty' => $alertPenalty,
                'incident_penalty' => $incidentPenalty,
                'active_alerts' => $stats['alerts_active'],
                'open_incidents' => $stats['incidents_open'],
            ],
        ];
    }

    private function percentage(int $part, int $total): int
    {
        if ($total <= 0) {
            return 100;
        }

        return (int) round(($part / $total) * 100);
    }

    private function healthGrade(int $score): string
    {
        return match (true) {
            $score >= 90 => 'excellent',
            $score >= 75 => 'good',
            $score >= 50 => 'degraded',
            $score >= 25 => 'poor',
            default => 'critical',
        };
    }

    private function cacheTtl(): int
    {
        return (int) config('orion.dashboard.cache_ttl', 60);
    }
}
