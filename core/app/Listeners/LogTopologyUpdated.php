<?php

namespace App\Listeners;

use App\Events\TopologyUpdated;
use Illuminate\Support\Facades\Log;

class LogTopologyUpdated
{
    public function handle(TopologyUpdated $event): void
    {
        Log::info('ORION topology updated', [
            'action' => $event->action,
            'nodes' => $event->graph['meta']['node_count'] ?? 0,
            'edges' => $event->graph['meta']['edge_count'] ?? 0,
        ]);
    }
}
