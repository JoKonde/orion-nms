<?php

namespace Database\Seeders;

use App\Enums\PermissionName;
use App\Enums\RoleName;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

/**
 * RolePermissionSeeder — cree les roles, les permissions et leurs associations.
 *
 * C'est ici qu'on definit "qui peut faire quoi" dans ORION.
 */
class RolePermissionSeeder extends Seeder
{
    public function run(): void
    {
        // Vide le cache des permissions (Spatie met les permissions en cache
        // pour la performance ; on le reinitialise apres un seeding).
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        // 1) Creer toutes les permissions definies dans l'enum.
        foreach (PermissionName::values() as $permission) {
            Permission::firstOrCreate([
                'name' => $permission,
                'guard_name' => 'web',
            ]);
        }

        // 2) Creer les roles.
        $admin = Role::firstOrCreate(['name' => RoleName::ADMIN->value, 'guard_name' => 'web']);
        $operator = Role::firstOrCreate(['name' => RoleName::OPERATOR->value, 'guard_name' => 'web']);
        $viewer = Role::firstOrCreate(['name' => RoleName::VIEWER->value, 'guard_name' => 'web']);

        // 3) Affecter les permissions aux roles.

        // ADMIN : toutes les permissions.
        $admin->syncPermissions(PermissionName::values());

        // OPERATOR : supervise le reseau (equipements CRUD sauf suppression users).
        $operator->syncPermissions([
            PermissionName::USERS_VIEW->value,
            PermissionName::ROLES_VIEW->value,
            PermissionName::DEVICES_VIEW->value,
            PermissionName::DEVICES_CREATE->value,
            PermissionName::DEVICES_UPDATE->value,
        ]);

        // VIEWER : lecture seule (utilisateurs + equipements).
        $viewer->syncPermissions([
            PermissionName::USERS_VIEW->value,
            PermissionName::DEVICES_VIEW->value,
        ]);
    }
}
