<?php

namespace App\Services;

use App\Enums\AlertStatus;
use App\Enums\DeviceStatus;
use App\Enums\IncidentStatus;
use App\Models\Alert;
use App\Models\Device;
use App\Models\Incident;
use App\Models\Metric;

/**
 * Construit le contexte JSON envoye a OpenRouter (donnees ORION reelles).
 */
class AiContextService
{
    public function __construct(private readonly DashboardService $dashboardService)
    {
    }

    /**
     * @return array<string, mixed>
     */
    public function buildNetworkSnapshot(): array
    {
        $overview = $this->dashboardService->getOverview();
        $health = $this->dashboardService->getHealth();

        $activeAlerts = Alert::query()
            ->with('device:id,name,ip_address')
            ->whereIn('status', AlertStatus::activeValues())
            ->latest('raised_at')
            ->limit(10)
            ->get()
            ->map(fn (Alert $a) => [
                'id' => $a->id,
                'title' => $a->title,
                'severity' => $a->severity?->value,
                'status' => $a->status?->value,
                'device' => $a->device?->name,
                'raised_at' => $a->raised_at?->toIso8601String(),
            ]);

        $openIncidents = Incident::query()
            ->with('device:id,name')
            ->whereIn('status', IncidentStatus::openValues())
            ->latest('opened_at')
            ->limit(10)
            ->get()
            ->map(fn (Incident $i) => [
                'id' => $i->id,
                'title' => $i->title,
                'priority' => $i->priority?->value,
                'status' => $i->status?->value,
                'device' => $i->device?->name,
            ]);

        $offlineDevices = Device::query()
            ->where('status', DeviceStatus::OFFLINE)
            ->limit(15)
            ->get(['id', 'name', 'ip_address', 'last_seen_at'])
            ->map(fn (Device $d) => [
                'name' => $d->name,
                'ip' => $d->ip_address,
                'last_seen_at' => $d->last_seen_at?->toIso8601String(),
            ]);

        return [
            'health_score' => $health->score,
            'health_grade' => $health->grade,
            'devices' => $overview->devices,
            'agents' => $overview->agents,
            'alerts_summary' => $overview->alerts,
            'incidents_summary' => $overview->incidents,
            'active_alerts' => $activeAlerts,
            'open_incidents' => $openIncidents,
            'offline_devices' => $offlineDevices,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function buildAlertContext(Alert $alert): array
    {
        $alert->loadMissing(['device', 'rule']);

        $metrics = [];
        if ($alert->device_id) {
            $metrics = $this->latestMetricsForDevice($alert->device_id);
        }

        return [
            'alert' => [
                'id' => $alert->id,
                'title' => $alert->title,
                'message' => $alert->message,
                'severity' => $alert->severity?->value,
                'status' => $alert->status?->value,
                'metric_type' => $alert->metric_type?->value,
                'metric_value' => $alert->metric_value,
                'raised_at' => $alert->raised_at?->toIso8601String(),
                'rule' => $alert->rule?->name,
            ],
            'device' => $alert->device ? [
                'name' => $alert->device->name,
                'ip' => $alert->device->ip_address,
                'status' => $alert->device->status?->value,
                'type' => $alert->device->type?->value,
            ] : null,
            'latest_metrics' => $metrics,
            'network' => $this->buildNetworkSnapshot(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function buildIncidentContext(Incident $incident): array
    {
        $incident->loadMissing(['device', 'alert', 'assignee']);

        $metrics = [];
        if ($incident->device_id) {
            $metrics = $this->latestMetricsForDevice($incident->device_id);
        }

        return [
            'incident' => [
                'id' => $incident->id,
                'title' => $incident->title,
                'description' => $incident->description,
                'priority' => $incident->priority?->value,
                'status' => $incident->status?->value,
                'opened_at' => $incident->opened_at?->toIso8601String(),
                'assignee' => $incident->assignee?->name,
            ],
            'linked_alert' => $incident->alert ? [
                'id' => $incident->alert->id,
                'title' => $incident->alert->title,
                'severity' => $incident->alert->severity?->value,
            ] : null,
            'device' => $incident->device ? [
                'name' => $incident->device->name,
                'ip' => $incident->device->ip_address,
                'status' => $incident->device->status?->value,
            ] : null,
            'latest_metrics' => $metrics,
            'network' => $this->buildNetworkSnapshot(),
        ];
    }

    /**
     * @return array<string, float|null>
     */
    private function latestMetricsForDevice(int $deviceId): array
    {
        $types = ['cpu', 'ram', 'disk_usage', 'uptime'];
        $out = [];

        foreach ($types as $type) {
            $out[$type] = Metric::query()
                ->where('device_id', $deviceId)
                ->where('metric_type', $type)
                ->orderByDesc('recorded_at')
                ->value('value');
        }

        return $out;
    }
}
