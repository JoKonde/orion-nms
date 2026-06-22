<?php

namespace App\Data;

use Spatie\LaravelData\Data;

/**
 * TopologyNodeData — noeud Cytoscape.js pour la cartographie reseau.
 *
 * Format attendu par Cytoscape :
 *   { "data": { "id": "1", "label": "Routeur", ... } }
 */
class TopologyNodeData extends Data
{
    public function __construct(
        public string $id,
        public string $label,
        public string $type,
        public string $status,
        public string $ip,
        public ?string $vendor,
    ) {
    }

    /**
     * @return array{data: array<string, mixed>}
     */
    public function toCytoscape(): array
    {
        return [
            'data' => [
                'id' => $this->id,
                'label' => $this->label,
                'type' => $this->type,
                'status' => $this->status,
                'ip' => $this->ip,
                'vendor' => $this->vendor,
            ],
        ];
    }
}
