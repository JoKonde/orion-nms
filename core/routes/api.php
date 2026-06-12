<?php

use App\Http\Controllers\Api\V1\AgentController;
use App\Http\Controllers\Api\V1\AlertController;
use App\Http\Controllers\Api\V1\AlertRuleController;
use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\DeviceController;
use App\Http\Controllers\Api\V1\MetricController;
use App\Http\Controllers\Api\V1\NetworkController;
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

    // Auto-enregistrement agent (cle bootstrap requise dans le header).
    Route::post('/agents/register', [AgentController::class, 'register']);

    // Routes agent authentifiees par cle API (pas Sanctum).
    Route::middleware('agent.api')->group(function () {
        Route::post('/agents/heartbeat', [AgentController::class, 'heartbeat']);
        Route::post('/agents/metrics', [MetricController::class, 'store']);
    });

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

        // Gestion des equipements reseau (Module 02 + decouverte Module 05)
        Route::post('/devices/discover', [DeviceController::class, 'discover']);

        // Detection reseau locale + scan Nmap (Module 05 — auto-detect subnet)
        Route::get('/network/detected', [NetworkController::class, 'detected']);
        Route::post('/network/discover', [NetworkController::class, 'discover']);

        Route::apiResource('devices', DeviceController::class);
        Route::get('/devices/{device}/metrics', [MetricController::class, 'index']);

        // Gestion des agents (Module 03) — consultation admin
        Route::get('/agents', [AgentController::class, 'index']);
        Route::get('/agents/{agent}', [AgentController::class, 'show']);
        Route::delete('/agents/{agent}', [AgentController::class, 'destroy']);

        // Alertes (Module 06)
        Route::apiResource('alert-rules', AlertRuleController::class);
        Route::get('/alerts', [AlertController::class, 'index']);
        Route::get('/alerts/{alert}', [AlertController::class, 'show']);
        Route::post('/alerts/{alert}/acknowledge', [AlertController::class, 'acknowledge']);
        Route::post('/alerts/{alert}/resolve', [AlertController::class, 'resolve']);
    });
});
