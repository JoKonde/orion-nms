<?php

namespace App\Services;

use App\Data\AgentRegisterData;
use App\Enums\AgentStatus;
use App\Enums\DeviceStatus;
use App\Enums\DeviceType;
use App\Enums\DiscoveryMethod;
use App\Models\Agent;
use App\Models\Device;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * AgentRegistrationService — logique d'auto-enregistrement des agents ORION.
 *
 * Flux :
 *   1. Verifier la cle bootstrap (securite premiere connexion)
 *   2. Creer un Device de type "server" lie a la machine
 *   3. Generer un UUID agent + une cle API (retournee UNE SEULE FOIS en clair)
 *   4. Stocker uniquement le hash de la cle API en base
 */
class AgentRegistrationService
{
    /**
     * @return array{agent: Agent, api_key: string}
     *
     * @throws AuthenticationException
     */
    public function register(AgentRegisterData $data, string $bootstrapKey): array
    {
        if (! hash_equals(config('orion.agent.bootstrap_key'), $bootstrapKey)) {
            throw new AuthenticationException('Cle bootstrap agent invalide.');
        }

        return DB::transaction(function () use ($data) {
            $agentUuid = $data->agent_uuid ?? (string) Str::uuid();

            // Si l'agent se re-enregistre avec le meme UUID, on refuse (deja connu).
            if (Agent::where('agent_uuid', $agentUuid)->exists()) {
                throw new AuthenticationException('Cet agent est deja enregistre.');
            }

            $device = Device::create([
                'name' => $data->hostname,
                'ip_address' => $data->ip_address,
                'mac_address' => $data->mac_address,
                'type' => DeviceType::SERVER,
                'status' => DeviceStatus::ONLINE,
                'discovery_method' => DiscoveryMethod::AGENT,
                'last_seen_at' => now(),
            ]);

            // Cle API : prefixe orion_ + 48 caracteres aleatoires.
            $apiKey = 'orion_'.Str::random(48);

            $agent = Agent::create([
                'device_id' => $device->id,
                'agent_uuid' => $agentUuid,
                'hostname' => $data->hostname,
                'os' => $data->os,
                'os_version' => $data->os_version,
                'architecture' => $data->architecture,
                'agent_version' => $data->agent_version,
                'api_key_hash' => Hash::make($apiKey),
                'status' => AgentStatus::ONLINE,
                'registered_at' => now(),
                'last_seen_at' => now(),
            ]);

            return [
                'agent' => $agent->load('device'),
                'api_key' => $apiKey,
            ];
        });
    }
}
