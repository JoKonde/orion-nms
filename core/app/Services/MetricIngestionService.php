<?php

namespace App\Services;

use App\Data\MetricBatchData;
use App\Events\MetricReceived;
use App\Models\Agent;
use App\Models\Metric;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * MetricIngestionService — reception et stockage des metriques agent.
 *
 * Point d'entree unique pour toute metrique entrant dans ORION Core.
 * Le Module 06 (Alerting) ecoutera l'Event MetricReceived pour evaluer les seuils.
 */
class MetricIngestionService
{
    /**
     * Ingere un batch de metriques pour l'agent authentifie.
     *
     * @return int Nombre de points inseres
     */
    public function ingest(Agent $agent, MetricBatchData $data): int
    {
        if ($data->agent_uuid !== $agent->agent_uuid) {
            abort(403, 'UUID agent incoherent avec la cle API.');
        }

        $deviceId = $agent->device_id;
        $now = now();
        $rows = [];

        foreach ($data->batch as $point) {
            $rows[] = [
                'device_id' => $deviceId,
                'metric_type' => $point->type->value,
                'value' => $point->value,
                'recorded_at' => Carbon::parse($point->recorded_at)->format('Y-m-d H:i:s'),
                'created_at' => $now,
            ];
        }

        if ($rows === []) {
            return 0;
        }

        return DB::transaction(function () use ($rows, $agent, $deviceId) {
            // insert() en masse : 1 requete SQL au lieu de N create() — critique pour le volume NMS.
            foreach (array_chunk($rows, 500) as $chunk) {
                Metric::insert($chunk);
            }

            // Event : decouple l'evaluation des alertes (Module 06) et le broadcast Reverb (Module 09).
            MetricReceived::dispatch($agent, $deviceId, count($rows));

            return count($rows);
        });
    }

    /**
     * Consulte les metriques brutes d'un device sur une periode.
     *
     * @return Collection<int, Metric>
     */
    public function queryRaw(
        int $deviceId,
        ?string $metricType,
        ?string $from,
        ?string $to,
        int $limit = 1000,
    ): Collection {
        $query = Metric::query()
            ->where('device_id', $deviceId)
            ->orderBy('recorded_at');

        if ($metricType) {
            $query->where('metric_type', $metricType);
        }
        if ($from) {
            $query->where('recorded_at', '>=', $from);
        }
        if ($to) {
            $query->where('recorded_at', '<=', $to);
        }

        return $query->limit($limit)->get();
    }

    /**
     * Consulte les agregats horaires (graphiques dashboard long terme).
     *
     * @return Collection<int, \App\Models\MetricHourly>
     */
    public function queryHourly(
        int $deviceId,
        ?string $metricType,
        ?string $from,
        ?string $to,
    ): Collection {
        $query = \App\Models\MetricHourly::query()
            ->where('device_id', $deviceId)
            ->orderBy('hour_start');

        if ($metricType) {
            $query->where('metric_type', $metricType);
        }
        if ($from) {
            $query->where('hour_start', '>=', $from);
        }
        if ($to) {
            $query->where('hour_start', '<=', $to);
        }

        return $query->get();
    }
}
