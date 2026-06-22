<?php

namespace App\Data;

use Spatie\LaravelData\Data;

/**
 * DashboardHealthData — score de sante reseau detaille.
 */
class DashboardHealthData extends Data
{
    public function __construct(
        public int $score,
        public string $grade,
        public array $factors,
        public string $generated_at,
    ) {
    }
}
