<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Support\Facades\Auth;

/**
 * AuthService — logique metier de l'authentification.
 *
 * Rappel du pattern impose dans ORION :
 *   Controller (mince) -> Service (logique metier) -> Model (Eloquent)
 *
 * Le controller ne fait QUE recevoir la requete et renvoyer la reponse.
 * Toute la logique (verifier le mot de passe, generer le token...) vit ici.
 * Avantage : reutilisable, testable, et le controller reste lisible.
 */
class AuthService
{
    /**
     * Authentifie un utilisateur et retourne un token Sanctum.
     *
     * @param  array{email: string, password: string}  $credentials
     * @return array{user: User, token: string}
     *
     * @throws AuthenticationException
     */
    public function login(array $credentials): array
    {
        // Auth::attempt verifie l'email + le mot de passe (hash) en base.
        if (! Auth::attempt(['email' => $credentials['email'], 'password' => $credentials['password']])) {
            throw new AuthenticationException('Identifiants invalides.');
        }

        /** @var User $user */
        $user = Auth::user();

        // On bloque les comptes desactives meme si le mot de passe est correct.
        if (! $user->is_active) {
            throw new AuthenticationException('Ce compte est desactive.');
        }

        // createToken genere un token personnel Sanctum (stocke hashe en base).
        // Le nom 'orion-dashboard' aide a identifier la provenance du token.
        $token = $user->createToken('orion-dashboard')->plainTextToken;

        return [
            'user' => $user,
            'token' => $token,
        ];
    }

    /**
     * Deconnecte l'utilisateur en supprimant le token utilise pour la requete courante.
     */
    public function logout(User $user): void
    {
        // currentAccessToken() = le token envoye dans l'en-tete Authorization.
        // On ne supprime que celui-ci (les autres sessions/appareils restent connectes).
        $user->currentAccessToken()->delete();
    }
}
