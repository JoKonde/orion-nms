<?php

namespace App\Models;

use App\Enums\AgentStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Agent extends Model
{
    use HasFactory;

    protected $fillable = [
        'device_id',
        'agent_uuid',
        'hostname',
        'os',
        'os_version',
        'architecture',
        'agent_version',
        'api_key_hash',
        'status',
        'registered_at',
        'last_seen_at',
    ];

    protected $casts = [
        'status' => AgentStatus::class,
        'registered_at' => 'datetime',
        'last_seen_at' => 'datetime',
    ];

    protected $hidden = [
        'api_key_hash',
    ];

    public function device(): BelongsTo
    {
        return $this->belongsTo(Device::class);
    }

    public function heartbeats(): HasMany
    {
        return $this->hasMany(Heartbeat::class);
    }
}
