<?php

namespace App\Models;

use App\Enums\AiInsightType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AiInsight extends Model
{
    protected $fillable = [
        'type',
        'title',
        'content',
        'user_id',
        'alert_id',
        'incident_id',
    ];

    protected $casts = [
        'type' => AiInsightType::class,
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function alert(): BelongsTo
    {
        return $this->belongsTo(Alert::class);
    }

    public function incident(): BelongsTo
    {
        return $this->belongsTo(Incident::class);
    }
}
