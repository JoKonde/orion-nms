<?php

namespace App\Data;

use App\Enums\MetricType;
use Spatie\LaravelData\Attributes\Validation\Date;
use Spatie\LaravelData\Attributes\Validation\In;
use Spatie\LaravelData\Attributes\Validation\Numeric;
use Spatie\LaravelData\Attributes\Validation\Required;
use Spatie\LaravelData\Data;

/**
 * MetricPointData — un point de metrique (type + valeur + horodatage).
 *
 * Utilise dans MetricBatchData pour valider chaque element du batch
 * envoye par l'agent (sync offline : l'agent peut renvoyer des centaines de points).
 */
class MetricPointData extends Data
{
    public function __construct(
        #[Required, In(MetricType::class)]
        public MetricType $type,

        #[Required, Numeric]
        public float $value,

        #[Required, Date]
        public string $recorded_at,
    ) {
    }
}
