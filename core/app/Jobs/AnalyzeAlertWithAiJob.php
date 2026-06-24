<?php

namespace App\Jobs;

use App\Enums\AiInsightType;
use App\Events\AiInsightCreated;
use App\Models\AiInsight;
use App\Models\Alert;
use App\Services\AiContextService;
use App\Services\OrionAiService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * Niveau 3 — analyse proactive d'une alerte critique (async).
 */
class AnalyzeAlertWithAiJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(public int $alertId)
    {
    }

    public function handle(OrionAiService $aiService, AiContextService $contextService): void
    {
        if (! $aiService->isEnabled()) {
            return;
        }

        $alert = Alert::with(['device', 'rule'])->find($this->alertId);
        if (! $alert) {
            return;
        }

        if (AiInsight::where('alert_id', $alert->id)->where('type', AiInsightType::PROACTIVE)->exists()) {
            return;
        }

        try {
            $context = $contextService->buildAlertContext($alert);
            $content = $aiService->proactiveAlertAnalysis($context);

            $insight = AiInsight::create([
                'type' => AiInsightType::PROACTIVE,
                'title' => "[IA] Alerte critique — {$alert->title}",
                'content' => $content,
                'alert_id' => $alert->id,
            ]);

            AiInsightCreated::dispatch($insight);
        } catch (\Throwable $e) {
            Log::warning('ORION AI proactive analysis failed', [
                'alert_id' => $alert->id,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
