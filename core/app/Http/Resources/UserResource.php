<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * UserResource — transforme un modele User en JSON propre pour l'API.
 *
 * On ne renvoie JAMAIS le modele brut ($user->toArray()) car cela exposerait
 * potentiellement des champs internes. La Resource controle exactement ce qui sort.
 *
 * @mixin \App\Models\User
 */
class UserResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'is_active' => $this->is_active,
            // whenLoaded : n'inclut les roles/permissions que s'ils ont ete charges
            // (evite une requete SQL supplementaire si on n'en a pas besoin).
            'roles' => $this->whenLoaded('roles', fn () => $this->roles->pluck('name')),
            'permissions' => $this->when(
                $this->relationLoaded('roles') || $this->relationLoaded('permissions'),
                fn () => $this->getAllPermissions()->pluck('name')
            ),
            'created_at' => $this->created_at,
        ];
    }
}
