<?php

namespace App\Models;

use App\Enums\DeviceStatus;
use App\Enums\DeviceType;
use App\Enums\DiscoveryMethod;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * Modele Device — represente un equipement reseau supervise par ORION.
 */
class Device extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'ip_address',
        'mac_address',
        'type',
        'vendor',
        'model',
        'firmware',
        'status',
        'discovery_method',
        'uptime_seconds',
        'description',
        'last_seen_at',
    ];

    protected $casts = [
        // Les enums PHP convertissent automatiquement la chaine en base <-> objet enum.
        'type' => DeviceType::class,
        'status' => DeviceStatus::class,
        'discovery_method' => DiscoveryMethod::class,
        'uptime_seconds' => 'integer',
        'last_seen_at' => 'datetime',
    ];

    public function agent(): HasOne
    {
        return $this->hasOne(Agent::class);
    }

    public function metrics(): HasMany
    {
        return $this->hasMany(Metric::class);
    }

    public function metricsHourly(): HasMany
    {
        return $this->hasMany(MetricHourly::class);
    }

    public function interfaces(): HasMany
    {
        return $this->hasMany(DeviceInterface::class);
    }

    public function alerts(): HasMany
    {
        return $this->hasMany(Alert::class);
    }
}
