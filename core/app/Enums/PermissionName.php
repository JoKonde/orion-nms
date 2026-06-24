<?php

namespace App\Enums;

/**
 * Enum des permissions ORION.
 *
 * Les permissions sont granulaires (action sur une ressource). On les regroupera
 * ensuite dans des roles via le seeder. Convention de nommage : "ressource.action".
 *
 * Pour l'instant (Module 01) on definit surtout la gestion des utilisateurs ;
 * les permissions des autres modules (devices, alerts, incidents...) seront
 * ajoutees au fur et a mesure de leur developpement.
 */
enum PermissionName: string
{
    // Gestion des utilisateurs
    case USERS_VIEW = 'users.view';
    case USERS_CREATE = 'users.create';
    case USERS_UPDATE = 'users.update';
    case USERS_DELETE = 'users.delete';

    // Gestion des roles
    case ROLES_VIEW = 'roles.view';
    case ROLES_MANAGE = 'roles.manage';

    // Gestion des equipements (Module 02)
    case DEVICES_VIEW = 'devices.view';
    case DEVICES_CREATE = 'devices.create';
    case DEVICES_UPDATE = 'devices.update';
    case DEVICES_DELETE = 'devices.delete';

    // Gestion des agents (Module 03)
    case AGENTS_VIEW = 'agents.view';
    case AGENTS_DELETE = 'agents.delete';

    // Alertes (Module 06)
    case ALERTS_VIEW = 'alerts.view';
    case ALERTS_MANAGE = 'alerts.manage';

    // Incidents (Module 07)
    case INCIDENTS_VIEW = 'incidents.view';
    case INCIDENTS_CREATE = 'incidents.create';
    case INCIDENTS_UPDATE = 'incidents.update';
    case INCIDENTS_ASSIGN = 'incidents.assign';
    case INCIDENTS_CLOSE = 'incidents.close';

    // Topologie reseau (Module 08)
    case TOPOLOGY_VIEW = 'topology.view';
    case TOPOLOGY_MANAGE = 'topology.manage';

    // Dashboard (Module 12)
    case DASHBOARD_VIEW = 'dashboard.view';

    // ORION AI (Module 10)
    case AI_USE = 'ai.use';

    // Rapports (Module 11)
    case REPORTS_VIEW = 'reports.view';

    /**
     * Retourne la liste de toutes les permissions sous forme de chaines.
     *
     * @return array<int, string>
     */
    public static function values(): array
    {
        return array_map(fn (self $perm) => $perm->value, self::cases());
    }
}
