<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    // HasApiTokens : fournit les tokens d'API (Sanctum) -> createToken(), tokens()...
    // HasRoles     : fournit la gestion des roles/permissions (Spatie) -> assignRole(), hasPermissionTo()...
    use HasApiTokens, HasFactory, Notifiable, HasRoles;

    /**
     * Attributs assignables en masse (create/update via tableau).
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'is_active',
    ];

    /**
     * Attributs caches lors de la serialisation JSON (jamais exposes a l'API).
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Conversions automatiques de types.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed', // Laravel hashe automatiquement le mot de passe a l'ecriture
        'is_active' => 'boolean',
    ];
}
