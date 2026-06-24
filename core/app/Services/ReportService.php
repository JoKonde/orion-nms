<?php

namespace App\Services;

use App\Enums\DeviceStatus;
use App\Enums\ReportType;
use App\Models\Agent;
use App\Models\Alert;
use App\Models\Device;
use App\Models\Incident;
use Carbon\Carbon;
use Illuminate\Support\Collection;

/**
 * ReportService — generation des rapports ORION (Module 11).
 */
class ReportService
{
    public function __construct(private readonly DashboardService $dashboardService)
    {
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function listTypes(): array
    {
        return array_map(fn (ReportType $type) => [
            'id' => $type->value,
            'label' => $type->label(),
            'description' => $type->description(),
            'supports_period' => $type->supportsPeriod(),
        ], ReportType::cases());
    }

    /**
     * @return array<string, mixed>
     */
    public function build(ReportType $type, ?Carbon $from = null, ?Carbon $to = null): array
    {
        [$from, $to] = $this->resolvePeriod($type, $from, $to);

        $report = match ($type) {
            ReportType::NETWORK_SUMMARY => $this->buildNetworkSummary(),
            ReportType::DEVICES => $this->buildDevices(),
            ReportType::AGENTS => $this->buildAgents(),
            ReportType::ALERTS => $this->buildAlerts($from, $to),
            ReportType::INCIDENTS => $this->buildIncidents($from, $to),
        };

        $report['type'] = $type->value;
        $report['title'] = $type->label();
        $report['generated_at'] = now()->toIso8601String();
        $report['period'] = $type->supportsPeriod()
            ? ['from' => $from->toIso8601String(), 'to' => $to->toIso8601String()]
            : null;

        return $report;
    }

    /**
     * @return array{content: string, filename: string, mime: string}
     */
    public function exportCsv(array $report): array
    {
        $handle = fopen('php://temp', 'r+');
        fwrite($handle, "\xEF\xBB\xBF");

        if (! empty($report['summary'])) {
            fputcsv($handle, ['Indicateur', 'Valeur'], ';');
            foreach ($report['summary'] as $row) {
                fputcsv($handle, [$row['label'], $row['value']], ';');
            }
            fputcsv($handle, [], ';');
        }

        if (! empty($report['columns'])) {
            fputcsv($handle, array_column($report['columns'], 'label'), ';');
            foreach ($report['rows'] as $row) {
                fputcsv($handle, array_map(
                    fn (array $col) => (string) ($row[$col['key']] ?? ''),
                    $report['columns'],
                ), ';');
            }
        }

        rewind($handle);
        $content = stream_get_contents($handle) ?: '';
        fclose($handle);

        return [
            'content' => $content,
            'filename' => $this->filename($report, 'csv'),
            'mime' => 'text/csv; charset=UTF-8',
        ];
    }

    public function filename(array $report, string $extension): string
    {
        $slug = str_replace('_', '-', (string) ($report['type'] ?? 'rapport'));
        $date = now()->format('Y-m-d_His');

        return "orion-{$slug}-{$date}.{$extension}";
    }

    /**
     * @return array{from: Carbon, to: Carbon}
     */
    private function resolvePeriod(ReportType $type, ?Carbon $from, ?Carbon $to): array
    {
        if (! $type->supportsPeriod()) {
            $to = now();
            $from = now()->subDays(30);

            return [$from, $to];
        }

        $to = $to ?? now();
        $from = $from ?? $to->copy()->subDays(30);

        if ($from->gt($to)) {
            [$from, $to] = [$to, $from];
        }

        return [$from, $to];
    }

    /**
     * @return array<string, mixed>
     */
    private function buildNetworkSummary(): array
    {
        $overview = $this->dashboardService->getOverview();
        $health = $this->dashboardService->getHealth();
        $data = $overview->toArray();
        $factors = $health->factors;

        $summary = [
            ['label' => 'Score sante', 'value' => (string) ($data['health']['score'] ?? 0).' / 100'],
            ['label' => 'Niveau sante', 'value' => (string) ($data['health']['grade'] ?? '—')],
            ['label' => 'Equipements total', 'value' => (string) ($data['devices']['total'] ?? 0)],
            ['label' => 'Equipements online', 'value' => (string) ($data['devices']['online'] ?? 0)],
            ['label' => 'Equipements offline', 'value' => (string) ($data['devices']['offline'] ?? 0)],
            ['label' => 'Agents total', 'value' => (string) ($data['agents']['total'] ?? 0)],
            ['label' => 'Agents online', 'value' => (string) ($data['agents']['online'] ?? 0)],
            ['label' => 'Alertes actives', 'value' => (string) ($data['alerts']['active'] ?? 0)],
            ['label' => 'Incidents ouverts', 'value' => (string) ($data['incidents']['open'] ?? 0)],
            ['label' => 'Liens topologie', 'value' => (string) ($data['topology']['links'] ?? 0)],
            ['label' => 'Dispo. equipements', 'value' => ($factors['device_availability_pct'] ?? 0).'%'],
            ['label' => 'Dispo. agents', 'value' => ($factors['agent_availability_pct'] ?? 0).'%'],
            ['label' => 'Penalite alertes', 'value' => '-'.($factors['alert_penalty'] ?? 0).' pts'],
            ['label' => 'Penalite incidents', 'value' => '-'.($factors['incident_penalty'] ?? 0).' pts'],
        ];

        $offlineDevices = Device::query()
            ->where('status', DeviceStatus::OFFLINE)
            ->orderBy('name')
            ->limit($this->maxRows())
            ->get();

        return [
            'summary' => $summary,
            'columns' => [
                ['key' => 'name', 'label' => 'Equipement'],
                ['key' => 'ip_address', 'label' => 'IP'],
                ['key' => 'status', 'label' => 'Statut'],
                ['key' => 'last_seen_at', 'label' => 'Derniere vue'],
            ],
            'rows' => $offlineDevices->map(fn (Device $d) => [
                'name' => $d->name,
                'ip_address' => $d->ip_address,
                'status' => $d->status?->value ?? '—',
                'last_seen_at' => $d->last_seen_at?->format('d/m/Y H:i') ?? '—',
            ])->all(),
            'sections' => [
                ['title' => 'Equipements hors ligne', 'empty' => 'Aucun equipement offline.'],
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function buildDevices(): array
    {
        $devices = Device::query()->orderBy('name')->limit($this->maxRows())->get();

        return [
            'summary' => [
                ['label' => 'Total equipements', 'value' => (string) $devices->count()],
            ],
            'columns' => [
                ['key' => 'name', 'label' => 'Nom'],
                ['key' => 'ip_address', 'label' => 'IP'],
                ['key' => 'status', 'label' => 'Statut'],
                ['key' => 'type', 'label' => 'Type'],
                ['key' => 'discovery_method', 'label' => 'Decouverte'],
                ['key' => 'last_seen_at', 'label' => 'Derniere vue'],
            ],
            'rows' => $devices->map(fn (Device $d) => [
                'name' => $d->name,
                'ip_address' => $d->ip_address,
                'status' => $d->status?->value ?? '—',
                'type' => $d->type?->value ?? '—',
                'discovery_method' => $d->discovery_method?->value ?? '—',
                'last_seen_at' => $d->last_seen_at?->format('d/m/Y H:i') ?? '—',
            ])->all(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function buildAgents(): array
    {
        $agents = Agent::query()->with('device')->orderBy('hostname')->limit($this->maxRows())->get();

        return [
            'summary' => [
                ['label' => 'Total agents', 'value' => (string) $agents->count()],
            ],
            'columns' => [
                ['key' => 'hostname', 'label' => 'Hostname'],
                ['key' => 'os', 'label' => 'OS'],
                ['key' => 'status', 'label' => 'Statut'],
                ['key' => 'device', 'label' => 'Equipement'],
                ['key' => 'ip_address', 'label' => 'IP'],
                ['key' => 'agent_version', 'label' => 'Version'],
                ['key' => 'last_seen_at', 'label' => 'Dernier contact'],
            ],
            'rows' => $agents->map(fn (Agent $a) => [
                'hostname' => $a->hostname,
                'os' => $a->os ?? '—',
                'status' => $a->status?->value ?? '—',
                'device' => $a->device?->name ?? '—',
                'ip_address' => $a->device?->ip_address ?? '—',
                'agent_version' => $a->agent_version ?? '—',
                'last_seen_at' => $a->last_seen_at?->format('d/m/Y H:i') ?? '—',
            ])->all(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function buildAlerts(Carbon $from, Carbon $to): array
    {
        $alerts = Alert::query()
            ->with('device')
            ->whereBetween('raised_at', [$from, $to])
            ->latest('raised_at')
            ->limit($this->maxRows())
            ->get();

        return [
            'summary' => $this->periodSummary($alerts, $from, $to, 'alertes'),
            'columns' => [
                ['key' => 'raised_at', 'label' => 'Levee le'],
                ['key' => 'severity', 'label' => 'Severite'],
                ['key' => 'status', 'label' => 'Statut'],
                ['key' => 'title', 'label' => 'Titre'],
                ['key' => 'device', 'label' => 'Equipement'],
                ['key' => 'resolved_at', 'label' => 'Resolue le'],
            ],
            'rows' => $alerts->map(fn (Alert $a) => [
                'raised_at' => $a->raised_at?->format('d/m/Y H:i') ?? '—',
                'severity' => $a->severity?->value ?? '—',
                'status' => $a->status?->value ?? '—',
                'title' => $a->title,
                'device' => $a->device?->name ?? '—',
                'resolved_at' => $a->resolved_at?->format('d/m/Y H:i') ?? '—',
            ])->all(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function buildIncidents(Carbon $from, Carbon $to): array
    {
        $incidents = Incident::query()
            ->with('device')
            ->whereBetween('opened_at', [$from, $to])
            ->latest('opened_at')
            ->limit($this->maxRows())
            ->get();

        return [
            'summary' => $this->periodSummary($incidents, $from, $to, 'incidents'),
            'columns' => [
                ['key' => 'opened_at', 'label' => 'Ouvert le'],
                ['key' => 'priority', 'label' => 'Priorite'],
                ['key' => 'status', 'label' => 'Statut'],
                ['key' => 'title', 'label' => 'Titre'],
                ['key' => 'device', 'label' => 'Equipement'],
                ['key' => 'closed_at', 'label' => 'Cloture le'],
            ],
            'rows' => $incidents->map(fn (Incident $i) => [
                'opened_at' => $i->opened_at?->format('d/m/Y H:i') ?? '—',
                'priority' => $i->priority?->value ?? '—',
                'status' => $i->status?->value ?? '—',
                'title' => $i->title,
                'device' => $i->device?->name ?? '—',
                'closed_at' => $i->closed_at?->format('d/m/Y H:i') ?? '—',
            ])->all(),
        ];
    }

    /**
     * @return array<int, array{label: string, value: string}>
     */
    private function periodSummary(Collection $items, Carbon $from, Carbon $to, string $label): array
    {
        return [
            ['label' => 'Periode du', 'value' => $from->format('d/m/Y')],
            ['label' => 'Periode au', 'value' => $to->format('d/m/Y')],
            ['label' => "Total {$label}", 'value' => (string) $items->count()],
        ];
    }

    private function maxRows(): int
    {
        return (int) config('orion.reports.max_rows', 5000);
    }
}
