<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin \App\Models\Metric|\App\Models\MetricHourly
 */
class MetricResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        // Support metriques brutes ET agregats horaires.
        if ($this->resource instanceof \App\Models\MetricHourly) {
            return [
                'metric_type' => $this->metric_type?->value ?? $this->metric_type,
                'avg_value' => $this->avg_value,
                'min_value' => $this->min_value,
                'max_value' => $this->max_value,
                'sample_count' => $this->sample_count,
                'hour_start' => $this->hour_start,
                'granularity' => 'hourly',
            ];
        }

        return [
            'metric_type' => $this->metric_type?->value ?? $this->metric_type,
            'value' => $this->value,
            'recorded_at' => $this->recorded_at,
            'granularity' => 'raw',
        ];
    }
}
