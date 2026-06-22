<?php

namespace App\Data;

use Spatie\LaravelData\Data;

/**
 * DashboardOverviewData — resume agrege pour la page d'accueil du dashboard React.
 */
class DashboardOverviewData extends Data
{
    public function __construct(
        public array $devices,
        public array $agents,
        public array $alerts,
        public array $incidents,
        public array $topology,
        public array $health,
        public string $generated_at,
    ) {
    }
}
