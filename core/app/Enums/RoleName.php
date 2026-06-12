<?php

namespace App\Enums;

/**
 * Enum des roles ORION.
 *
 * On centralise les noms de roles dans un enum plutot que d'ecrire des chaines
 * "admin" un peu partout : cela evite les fautes de frappe et facilite la maintenance.
 */
enum RoleName: string
{
    case ADMIN = 'admin';       // Acces total (gestion utilisateurs, config, tout)
    case OPERATOR = 'operator'; // Gere les equipements, alertes, incidents
    case VIEWER = 'viewer';     // Lecture seule (consultation dashboard)

    /**
     * Retourne la liste de tous les roles sous forme de tableau de chaines.
     *
     * @return array<int, string>
     */
    public static function values(): array
    {
        return array_map(fn (self $role) => $role->value, self::cases());
    }
}
