<?php

namespace App\Data;

use Spatie\LaravelData\Data;

/**
 * TopologyEdgeData — lien Cytoscape.js entre deux equipements.
 */
class TopologyEdgeData extends Data
{
    public function __construct(
        public string $id,
        public string $source,
        public string $target,
        public string $status,
        public string $link_type,
        public ?string $source_interface,
        public ?string $target_interface,
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
                'source' => $this->source,
                'target' => $this->target,
                'status' => $this->status,
                'link_type' => $this->link_type,
                'source_interface' => $this->source_interface,
                'target_interface' => $this->target_interface,
            ],
        ];
    }
}
