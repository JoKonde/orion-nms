<?php

namespace App\Jobs;

use App\Enums\AgentStatus;
use App\Events\AgentWentOffline;
use App\Models\Agent;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

/**
 * CheckAgentsOfflineJob — detecte les agents qui ne repondent plus.
 *
 * POURQUOI un Job (et pas du code direct dans le Scheduler) ?
 * ------------------------------------------------------------
 * Un Job est une tache asynchrone mise en file d'attente (Redis via Predis).
 * Avantages dans un NMS :
 *   - Ne bloque pas le scheduler si la verification prend du temps (milliers d'agents)
 *   - Retry automatique en cas d'echec temporaire (connexion DB, etc.)
 *   - Execute par un worker : php artisan queue:work
 *
 * Le Scheduler (Console/Kernel.php) declenche ce Job chaque minute ;
 * le worker Redis le prend et l'execute en arriere-plan.
 *
 * En local sans Redis : mettre QUEUE_CONNECTION=sync dans .env
 * (le Job s'execute immediatement au lieu d'etre mis en file).
 */
class CheckAgentsOfflineJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function handle(): void
    {
        $timeout = config('orion.agent.heartbeat_timeout', 120);
        $threshold = now()->subSeconds($timeout);

        // Agents marques online mais sans heartbeat recent -> offline.
        Agent::query()
            ->where('status', AgentStatus::ONLINE)
            ->where(function ($query) use ($threshold) {
                $query->where('last_seen_at', '<', $threshold)
                    ->orWhereNull('last_seen_at');
            })
            ->each(function (Agent $agent) {
                $agent->update(['status' => AgentStatus::OFFLINE]);

                // On declenche un Event plutot que de tout faire ici (voir AgentWentOffline).
                AgentWentOffline::dispatch($agent);
            });
    }
}
