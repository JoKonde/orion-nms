<?php

namespace App\Events;

use App\Models\Alert;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * AlertRaised — declenche quand une nouvelle alerte est creee.
 *
 * Listeners :
 *   - LogAlertRaised (Module 06)
 *   - BroadcastAlertListener (Module 09 Reverb — a brancher plus tard)
 *   - Escalade incident (Module 07)
 */
class AlertRaised
{
    use Dispatchable, SerializesModels;

    public function __construct(public Alert $alert)
    {
    }
}
