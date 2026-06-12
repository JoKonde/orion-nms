<?php

namespace App\Jobs;

use App\Models\Metric;
use App\Models\MetricHourly;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;

/**
 * AggregateMetricsJob — calcule les moyennes horaires des metriques brutes.
 *
 * POURQUOI un Job planifie (Scheduler) pour l'agregation ?
 * ------------------------------------------------------------
 * Les metriques brutes arrivent toutes les 30s-60s par agent. Sur 1 mois,
 * ca fait des millions de lignes. Les graphiques dashboard utilisent plutot
 * metrics_hourly (1 ligne/heure/type/device).
 *
 * Ce Job tourne chaque heure via le Scheduler et pre-calcule avg/min/max.
 * Le dashboard React interrogera metrics_hourly pour les vues long terme.
 */
class AggregateMetricsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function handle(): void
    {
        // Heure precedente complete (ex: 14:00:00 -> 14:59:59).
        $hourStart = now()->subHour()->startOfHour();
        $hourEnd = $hourStart->copy()->endOfHour();

        $aggregates = Metric::query()
            ->select([
                'device_id',
                'metric_type',
                DB::raw('AVG(value) as avg_value'),
                DB::raw('MIN(value) as min_value'),
                DB::raw('MAX(value) as max_value'),
                DB::raw('COUNT(*) as sample_count'),
            ])
            ->whereBetween('recorded_at', [$hourStart, $hourEnd])
            ->groupBy('device_id', 'metric_type')
            ->get();

        foreach ($aggregates as $row) {
            MetricHourly::updateOrCreate(
                [
                    'device_id' => $row->device_id,
                    'metric_type' => $row->metric_type,
                    'hour_start' => $hourStart,
                ],
                [
                    'avg_value' => $row->avg_value,
                    'min_value' => $row->min_value,
                    'max_value' => $row->max_value,
                    'sample_count' => $row->sample_count,
                ]
            );
        }
    }
}
