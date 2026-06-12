<?php

namespace App\Events;

use App\Models\Agent;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * AgentWentOffline — declenche quand un agent passe en statut offline.
 *
 * POURQUOI Events + Listeners ?
 * -----------------------------
 * Au lieu d'appeler directement dans le Job : "logger + mettre a jour device +
 * envoyer notification + broadcaster Reverb", on emet un Event.
 *
 * Chaque Listener fait UNE chose (principe de responsabilite unique).
 * On peut ajouter un Listener plus tard (email, Slack, Reverb Module 09)
 * sans modifier le Job ni le service heartbeat.
 *
 * C'est le pattern "Observer" de Laravel, ideal pour un NMS ou une alerte
 * declenche plusieurs reactions en parallele.
 */
class AgentWentOffline
{
    use Dispatchable, SerializesModels;

    public function __construct(public Agent $agent)
    {
    }
}
