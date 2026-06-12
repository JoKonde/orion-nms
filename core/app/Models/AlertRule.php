<?php

namespace App\Models;

use App\Enums\AlertOperator;
use App\Enums\AlertRuleType;
use App\Enums\AlertSeverity;
use App\Enums\MetricType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AlertRule extends Model
{
    protected $fillable = [
        'name',
        'description',
        'rule_type',
        'metric_type',
        'operator',
        'threshold',
        'severity',
        'device_id',
        'is_enabled',
        'cooldown_minutes',
    ];

    protected $casts = [
        'rule_type' => AlertRuleType::class,
        'metric_type' => MetricType::class,
        'operator' => AlertOperator::class,
        'severity' => AlertSeverity::class,
        'threshold' => 'float',
        'is_enabled' => 'boolean',
        'cooldown_minutes' => 'integer',
    ];

    public function device(): BelongsTo
    {
        return $this->belongsTo(Device::class);
    }

    public function alerts(): HasMany
    {
        return $this->hasMany(Alert::class);
    }
}
