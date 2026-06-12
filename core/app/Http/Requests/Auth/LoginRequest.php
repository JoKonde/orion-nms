<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;

/**
 * LoginRequest — valide les donnees de connexion.
 *
 * Un Form Request regroupe en un seul endroit :
 *   - authorize() : qui a le droit de faire cette action ?
 *   - rules()     : quelles sont les regles de validation ?
 *
 * Le controller recoit deja des donnees validees -> il reste mince et sur.
 */
class LoginRequest extends FormRequest
{
    /**
     * Tout le monde peut tenter de se connecter (route publique).
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ];
    }

    /**
     * Messages d'erreur personnalises en francais.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'email.required' => "L'adresse email est obligatoire.",
            'email.email' => "L'adresse email n'est pas valide.",
            'password.required' => 'Le mot de passe est obligatoire.',
        ];
    }
}
