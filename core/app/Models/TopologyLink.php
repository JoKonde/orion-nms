<?php

namespace App\Models;

use App\Enums\TopologyLinkStatus;
use App\Enums\TopologyLinkType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TopologyLink extends Model
{
    protected $fillable = [
        'source_device_id',
        'target_device_id',
        'link_type',
        'link_status',
        'source_interface',
        'target_interface',
        'metadata',
    ];

    protected $casts = [
        'link_type' => TopologyLinkType::class,
        'link_status' => TopologyLinkStatus::class,
        'metadata' => 'array',
    ];

    public function sourceDevice(): BelongsTo
    {
        return $this->belongsTo(Device::class, 'source_device_id');
    }

    public function targetDevice(): BelongsTo
    {
        return $this->belongsTo(Device::class, 'target_device_id');
    }
}
