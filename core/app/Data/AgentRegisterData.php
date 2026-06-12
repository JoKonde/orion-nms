<?php

namespace App\Data;

use Spatie\LaravelData\Attributes\Validation\In;
use Spatie\LaravelData\Attributes\Validation\Ip;
use Spatie\LaravelData\Attributes\Validation\Max;
use Spatie\LaravelData\Attributes\Validation\Nullable;
use Spatie\LaravelData\Attributes\Validation\Required;
use Spatie\LaravelData\Attributes\Validation\StringType;
use Spatie\LaravelData\Attributes\Validation\Uuid;
use Spatie\LaravelData\Data;

/**
 * AgentRegisterData — DTO pour l'auto-enregistrement d'un agent ORION.
 *
 * Spatie Laravel Data structure les donnees envoyees par l'agent Electron
 * de maniere typee et reutilisable dans AgentRegistrationService.
 */
class AgentRegisterData extends Data
{
    public function __construct(
        #[Required, StringType, Max(255)]
        public string $hostname,

        #[Required, In(['windows', 'linux'])]
        public string $os,

        #[Nullable, StringType, Max(255)]
        public ?string $os_version,

        #[Nullable, StringType, Max(50)]
        public ?string $architecture,

        #[Nullable, StringType, Max(50)]
        public ?string $agent_version,

        #[Required, Ip]
        public string $ip_address,

        #[Nullable, StringType, Max(17)]
        public ?string $mac_address,

        // UUID optionnel : l'agent peut en generer un localement pour se reidentifier.
        #[Nullable, Uuid]
        public ?string $agent_uuid,
    ) {
    }
}
