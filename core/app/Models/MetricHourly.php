<?php

namespace App\Models;

use App\Enums\MetricType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MetricHourly extends Model
{
    public $timestamps = false;

    protected $table = 'metrics_hourly';

    protected $fillable = [
        'device_id',
        'metric_type',
        'avg_value',
        'min_value',
        'max_value',
        'sample_count',
        'hour_start',
    ];

    protected $casts = [
        'metric_type' => MetricType::class,
        'avg_value' => 'decimal:4',
        'min_value' => 'decimal:4',
        'max_value' => 'decimal:4',
        'sample_count' => 'integer',
        'hour_start' => 'datetime',
    ];

    public function device(): BelongsTo
    {
        return $this->belongsTo(Device::class);
    }
}
