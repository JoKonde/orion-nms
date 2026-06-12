<?php

namespace App\Data;

use App\Enums\IncidentStatus;
use Spatie\LaravelData\Attributes\Validation\In;
use Spatie\LaravelData\Attributes\Validation\IntegerType;
use Spatie\LaravelData\Attributes\Validation\Nullable;
use Spatie\LaravelData\Attributes\Validation\Required;
use Spatie\LaravelData\Attributes\Validation\StringType;
use Spatie\LaravelData\Data;

/**
 * IncidentTransitionData — DTO pour les transitions de cycle de vie.
 *
 * Utilise par assign / start / resolve / close.
 */
class IncidentTransitionData extends Data
{
    public function __construct(
        #[Required, In(IncidentStatus::class)]
        public IncidentStatus $target_status,

        #[Nullable, IntegerType]
        public ?int $assigned_to,

        #[Nullable, StringType]
        public ?string $resolution_notes,
    ) {
    }
}
