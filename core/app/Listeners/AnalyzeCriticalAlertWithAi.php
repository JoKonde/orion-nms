<?php

namespace App\Listeners;

use App\Enums\AlertSeverity;
use App\Events\AlertRaised;
use App\Jobs\AnalyzeAlertWithAiJob;
use App\Services\OrionAiService;

/**
 * Niveau 3 — declenche une analyse IA sur alerte critical.
 */
class AnalyzeCriticalAlertWithAi
{
    public function __construct(private readonly OrionAiService $aiService)
    {
    }

    public function handle(AlertRaised $event): void
    {
        if (! $this->aiService->isEnabled()) {
            return;
        }

        if ($event->alert->severity !== AlertSeverity::CRITICAL) {
            return;
        }

        AnalyzeAlertWithAiJob::dispatch($event->alert->id);
    }
}
