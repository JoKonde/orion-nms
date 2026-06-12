<?php

use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\UserController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Routes API — ORION Core
|--------------------------------------------------------------------------
| Toutes les routes sont prefixees par /api (config Laravel) puis par /v1
| (versionnement de l'API : permet de faire evoluer l'API sans casser les
| clients existants -> on pourra creer un /v2 plus tard).
*/

Route::prefix('v1')->group(function () {

    // --- Routes publiques (pas besoin d'etre connecte) ---
    Route::post('/auth/login', [AuthController::class, 'login']);

    // --- Routes protegees (token Sanctum obligatoire) ---
    Route::middleware('auth:sanctum')->group(function () {

        // Authentification
        Route::post('/auth/logout', [AuthController::class, 'logout']);
        Route::get('/auth/me', [AuthController::class, 'me']);

        // Gestion des utilisateurs (Module 01)
        // Les permissions fines (users.view / users.delete) protegent la lecture
        // et la suppression. La creation/modification sont protegees directement
        // dans les Form Requests (StoreUserRequest / UpdateUserRequest).
        Route::get('/users', [UserController::class, 'index'])->middleware('permission:users.view');
        Route::get('/users/{user}', [UserController::class, 'show'])->middleware('permission:users.view');
        Route::post('/users', [UserController::class, 'store']);
        Route::match(['put', 'patch'], '/users/{user}', [UserController::class, 'update']);
        Route::delete('/users/{user}', [UserController::class, 'destroy'])->middleware('permission:users.delete');
    });
});
