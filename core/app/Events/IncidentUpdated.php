<?php

namespace App\Events;

use App\Models\Incident;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * IncidentUpdated — declenche a chaque changement d'incident.
 *
 * action : created | updated | assigned | started | resolved | closed |
 *          escalated | auto_escalated
 *
 * Module 09 branchera Reverb broadcast ici.
 */
class IncidentUpdated
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public Incident $incident,
        public string $action,
    ) {
    }
}
