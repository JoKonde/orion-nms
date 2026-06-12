<?php

namespace App\Models;

use App\Enums\MetricType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Metric extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'device_id',
        'metric_type',
        'value',
        'recorded_at',
        'created_at',
    ];

    protected $casts = [
        'metric_type' => MetricType::class,
        'value' => 'decimal:4',
        'recorded_at' => 'datetime',
        'created_at' => 'datetime',
    ];

    public function device(): BelongsTo
    {
        return $this->belongsTo(Device::class);
    }
}
