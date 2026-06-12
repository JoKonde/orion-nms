<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Resources\UserResource;
use App\Services\AuthService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * AuthController — points d'entree de l'authentification (API V1).
 *
 * Remarque : le controller est volontairement "mince".
 * Il ne fait que : recevoir la requete validee -> appeler le service -> formater la reponse.
 */
class AuthController extends Controller
{
    // Injection de dependance : Laravel fournit automatiquement AuthService.
    public function __construct(private readonly AuthService $authService)
    {
    }

    /**
     * POST /api/v1/auth/login
     */
    public function login(LoginRequest $request): JsonResponse
    {
        $result = $this->authService->login($request->validated());

        return response()->json([
            'token' => $result['token'],
            'token_type' => 'Bearer',
            'user' => new UserResource($result['user']->load('roles')),
        ]);
    }

    /**
     * POST /api/v1/auth/logout
     */
    public function logout(Request $request): JsonResponse
    {
        $this->authService->logout($request->user());

        return response()->json(['message' => 'Deconnexion reussie.']);
    }

    /**
     * GET /api/v1/auth/me — retourne l'utilisateur connecte + ses roles/permissions.
     */
    public function me(Request $request): UserResource
    {
        return new UserResource($request->user()->load('roles'));
    }
}
