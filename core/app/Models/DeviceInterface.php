<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DeviceInterface extends Model
{
    protected $fillable = [
        'device_id',
        'name',
        'mac_address',
        'speed_bps',
        'admin_status',
        'oper_status',
        'in_octets',
        'out_octets',
    ];

    protected $casts = [
        'speed_bps' => 'integer',
        'in_octets' => 'integer',
        'out_octets' => 'integer',
    ];

    public function device(): BelongsTo
    {
        return $this->belongsTo(Device::class);
    }
}
