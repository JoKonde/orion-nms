<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\User\StoreUserRequest;
use App\Http\Requests\User\UpdateUserRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use App\Services\UserService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

/**
 * UserController — CRUD des utilisateurs (API V1).
 *
 * La logique metier est dans UserService ; ici on ne fait qu'orchestrer.
 */
class UserController extends Controller
{
    public function __construct(private readonly UserService $userService)
    {
    }

    /**
     * GET /api/v1/users
     */
    public function index(): AnonymousResourceCollection
    {
        return UserResource::collection($this->userService->paginate());
    }

    /**
     * POST /api/v1/users
     */
    public function store(StoreUserRequest $request): JsonResponse
    {
        $user = $this->userService->create($request->validated());

        return (new UserResource($user))
            ->response()
            ->setStatusCode(201); // 201 Created
    }

    /**
     * GET /api/v1/users/{user}
     * Route model binding : Laravel charge automatiquement le User par son id.
     */
    public function show(User $user): UserResource
    {
        return new UserResource($user->load('roles'));
    }

    /**
     * PUT/PATCH /api/v1/users/{user}
     */
    public function update(UpdateUserRequest $request, User $user): UserResource
    {
        $user = $this->userService->update($user, $request->validated());

        return new UserResource($user);
    }

    /**
     * DELETE /api/v1/users/{user}
     */
    public function destroy(User $user): JsonResponse
    {
        $this->userService->delete($user);

        return response()->json(['message' => 'Utilisateur supprime.']);
    }
}
