<?php

namespace App\Data;

use Spatie\LaravelData\Attributes\Validation\ArrayType;
use Spatie\LaravelData\Attributes\Validation\Nullable;
use Spatie\LaravelData\Attributes\Validation\Required;
use Spatie\LaravelData\Attributes\Validation\Uuid;
use Spatie\LaravelData\Data;

/**
 * HeartbeatData — DTO pour le signal de vie periodique de l'agent.
 *
 * L'agent envoie son UUID + un payload JSON optionnel (etat local, version, etc.).
 */
class HeartbeatData extends Data
{
    public function __construct(
        #[Required, Uuid]
        public string $agent_uuid,

        #[Nullable, ArrayType]
        public ?array $payload,
    ) {
    }
}
