<?php

namespace Database\Seeders;

use App\Enums\RoleName;
use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Point d'entree du seeding (php artisan db:seed).
     */
    public function run(): void
    {
        // 1) Roles + permissions d'abord (l'admin en a besoin pour son role).
        $this->call(RolePermissionSeeder::class);

        // 2) Compte administrateur par defaut.
        //    updateOrCreate evite les doublons si on relance le seeder.
        $admin = User::updateOrCreate(
            ['email' => 'admin@orion.local'],
            [
                'name' => 'Administrateur ORION',
                'password' => 'Password123!', // hashe automatiquement (cast 'hashed')
                'is_active' => true,
            ]
        );

        // On lui donne le role admin (= toutes les permissions).
        $admin->syncRoles([RoleName::ADMIN->value]);

        // 3) Equipements de demonstration (Module 02).
        $this->call(DeviceSeeder::class);
    }
}
