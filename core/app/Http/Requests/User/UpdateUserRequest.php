<?php

namespace App\Http\Requests\User;

use App\Enums\PermissionName;
use App\Enums\RoleName;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

/**
 * UpdateUserRequest — valide la modification d'un utilisateur.
 *
 * Toutes les regles utilisent "sometimes" : les champs sont valides UNIQUEMENT
 * s'ils sont presents -> permet une mise a jour partielle (PATCH-like).
 */
class UpdateUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can(PermissionName::USERS_UPDATE->value) ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        // L'utilisateur en cours de modification (injecte via le route model binding).
        $userId = $this->route('user')?->id;

        return [
            'name' => ['sometimes', 'string', 'max:255'],
            // unique en ignorant l'utilisateur courant (sinon il ne pourrait pas garder son email).
            'email' => ['sometimes', 'email', 'max:255', Rule::unique('users', 'email')->ignore($userId)],
            'password' => ['sometimes', 'confirmed', Password::defaults()],
            'is_active' => ['sometimes', 'boolean'],
            'roles' => ['sometimes', 'array'],
            'roles.*' => [Rule::in(RoleName::values())],
        ];
    }
}
