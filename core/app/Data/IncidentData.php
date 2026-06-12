<?php

namespace App\Data;

use App\Enums\IncidentPriority;
use Spatie\LaravelData\Attributes\Validation\In;
use Spatie\LaravelData\Attributes\Validation\IntegerType;
use Spatie\LaravelData\Attributes\Validation\Max;
use Spatie\LaravelData\Attributes\Validation\Nullable;
use Spatie\LaravelData\Attributes\Validation\Required;
use Spatie\LaravelData\Attributes\Validation\StringType;
use Spatie\LaravelData\Data;

/**
 * IncidentData — DTO pour creer un incident manuellement.
 */
class IncidentData extends Data
{
    public function __construct(
        #[Required, StringType, Max(255)]
        public string $title,

        #[Nullable, StringType]
        public ?string $description,

        #[Required, In(IncidentPriority::class)]
        public IncidentPriority $priority,

        #[Nullable, IntegerType]
        public ?int $device_id,

        #[Nullable, IntegerType]
        public ?int $alert_id,
    ) {
    }
}
