<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

/**
 * UserService — logique metier de la gestion des utilisateurs (CRUD + roles).
 *
 * Toute la logique de creation/modification/suppression d'utilisateur passe ici,
 * y compris l'affectation des roles (Spatie Permission).
 */
class UserService
{
    /**
     * Liste paginee des utilisateurs avec leurs roles.
     */
    public function paginate(int $perPage = 15): LengthAwarePaginator
    {
        // with('roles') = eager loading : evite le probleme N+1 (1 requete au lieu de N).
        return User::query()
            ->with('roles')
            ->latest()
            ->paginate($perPage);
    }

    /**
     * Cree un utilisateur et lui assigne ses roles.
     *
     * @param  array{name: string, email: string, password: string, roles?: array<int, string>, is_active?: bool}  $data
     */
    public function create(array $data): User
    {
        // DB::transaction : si l'affectation des roles echoue, la creation de
        // l'utilisateur est annulee (tout ou rien). Garantit la coherence des donnees.
        return DB::transaction(function () use ($data) {
            $user = User::create([
                'name' => $data['name'],
                'email' => $data['email'],
                'password' => $data['password'], // hashe automatiquement (cast 'hashed' du modele)
                'is_active' => $data['is_active'] ?? true,
            ]);

            if (! empty($data['roles'])) {
                $user->syncRoles($data['roles']);
            }

            return $user->load('roles');
        });
    }

    /**
     * Met a jour un utilisateur (et ses roles si fournis).
     *
     * @param  array<string, mixed>  $data
     */
    public function update(User $user, array $data): User
    {
        return DB::transaction(function () use ($user, $data) {
            // array_filter retire les cles non transmises pour ne mettre a jour
            // que les champs reellement envoyes (mise a jour partielle).
            $user->fill(array_filter([
                'name' => $data['name'] ?? null,
                'email' => $data['email'] ?? null,
                'password' => $data['password'] ?? null,
            ], fn ($value) => ! is_null($value)));

            // is_active peut valoir false : on le traite a part (array_filter le retirerait).
            if (array_key_exists('is_active', $data)) {
                $user->is_active = $data['is_active'];
            }

            $user->save();

            if (array_key_exists('roles', $data)) {
                $user->syncRoles($data['roles'] ?? []);
            }

            return $user->load('roles');
        });
    }

    /**
     * Supprime un utilisateur.
     */
    public function delete(User $user): void
    {
        $user->delete();
    }
}
