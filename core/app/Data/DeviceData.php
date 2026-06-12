<?php

namespace App\Data;

use App\Enums\DeviceStatus;
use App\Enums\DeviceType;
use App\Enums\DiscoveryMethod;
use Spatie\LaravelData\Attributes\Validation\In;
use Spatie\LaravelData\Attributes\Validation\Ip;
use Spatie\LaravelData\Attributes\Validation\Max;
use Spatie\LaravelData\Attributes\Validation\Nullable;
use Spatie\LaravelData\Attributes\Validation\Required;
use Spatie\LaravelData\Attributes\Validation\StringType;
use Spatie\LaravelData\Data;

/**
 * DeviceData — DTO (Data Transfer Object) typé pour un equipement ORION.
 *
 * POURQUOI Spatie Laravel Data ?
 * ------------------------------
 * Sans DTO, on passe des tableaux PHP ($data['ip_address']) entre le controller,
 * le service et les jobs : pas de typage, pas d'autocompletion, erreurs possibles
 * sur les cles ("ip_adress" au lieu de "ip_address").
 *
 * Avec DeviceData :
 *   - Chaque champ est type (string, enum, int...)
 *   - La validation peut vivre sur les attributs du DTO
 *   - On convertit facilement : DeviceData::from($request->validated())
 *   - Reutilisable dans les Jobs (Module 05) et l'ingestion agent (Module 04)
 *
 * C'est le "contrat" de donnees entre les couches de ORION.
 */
class DeviceData extends Data
{
    public function __construct(
        #[Required, StringType, Max(255)]
        public string $name,

        #[Required, Ip]
        public string $ip_address,

        #[Nullable, StringType, Max(17)]
        public ?string $mac_address,

        #[Required, In(DeviceType::class)]
        public DeviceType $type,

        #[Nullable, StringType, Max(255)]
        public ?string $vendor,

        #[Nullable, StringType, Max(255)]
        public ?string $model,

        #[Nullable, StringType, Max(255)]
        public ?string $firmware,

        #[Nullable, In(DeviceStatus::class)]
        public ?DeviceStatus $status,

        #[Nullable, In(DiscoveryMethod::class)]
        public ?DiscoveryMethod $discovery_method,

        #[Nullable]
        public ?int $uptime_seconds,

        #[Nullable, StringType]
        public ?string $description,
    ) {
    }
}
