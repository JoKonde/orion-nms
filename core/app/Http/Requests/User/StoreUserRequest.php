<?php

namespace App\Http\Requests\User;

use App\Enums\PermissionName;
use App\Enums\RoleName;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

/**
 * StoreUserRequest — valide la creation d'un utilisateur.
 */
class StoreUserRequest extends FormRequest
{
    /**
     * Seul un utilisateur ayant la permission "users.create" peut creer un compte.
     * (Verifie automatiquement avant d'executer le controller.)
     */
    public function authorize(): bool
    {
        return $this->user()?->can(PermissionName::USERS_CREATE->value) ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            // Password::defaults() applique une politique de mot de passe robuste.
            'password' => ['required', 'confirmed', Password::defaults()],
            'is_active' => ['sometimes', 'boolean'],
            'roles' => ['sometimes', 'array'],
            // Chaque role envoye doit exister dans notre enum RoleName.
            'roles.*' => [Rule::in(RoleName::values())],
        ];
    }
}
