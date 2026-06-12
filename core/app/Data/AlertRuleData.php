<?php

namespace App\Data;

use App\Enums\AlertOperator;
use App\Enums\AlertRuleType;
use App\Enums\AlertSeverity;
use App\Enums\MetricType;
use Spatie\LaravelData\Attributes\Validation\BooleanType;
use Spatie\LaravelData\Attributes\Validation\In;
use Spatie\LaravelData\Attributes\Validation\IntegerType;
use Spatie\LaravelData\Attributes\Validation\Max;
use Spatie\LaravelData\Attributes\Validation\Min;
use Spatie\LaravelData\Attributes\Validation\Nullable;
use Spatie\LaravelData\Attributes\Validation\Numeric;
use Spatie\LaravelData\Attributes\Validation\Required;
use Spatie\LaravelData\Attributes\Validation\StringType;
use Spatie\LaravelData\Data;

/**
 * AlertRuleData — DTO pour creer/modifier une regle d'alerte ORION.
 */
class AlertRuleData extends Data
{
    public function __construct(
        #[Required, StringType, Max(255)]
        public string $name,

        #[Nullable, StringType]
        public ?string $description,

        #[Required, In(AlertRuleType::class)]
        public AlertRuleType $rule_type,

        #[Nullable, In(MetricType::class)]
        public ?MetricType $metric_type,

        #[Nullable, In(AlertOperator::class)]
        public ?AlertOperator $operator,

        #[Nullable, Numeric]
        public ?float $threshold,

        #[Required, In(AlertSeverity::class)]
        public AlertSeverity $severity,

        #[Nullable, IntegerType, Min(1)]
        public ?int $device_id,

        #[Nullable, BooleanType]
        public ?bool $is_enabled,

        #[Nullable, IntegerType, Min(1), Max(1440)]
        public ?int $cooldown_minutes,
    ) {
    }
}
