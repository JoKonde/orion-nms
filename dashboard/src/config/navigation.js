/**
 * Definition du menu sidebar — chaque item est lie a une permission backend.
 * Les routes "soon" seront branchees aux Modules 10/11.
 */
export const NAV_SECTIONS = [
  {
    label: 'Supervision',
    items: [
      {
        id: 'overview',
        label: 'Vue d\'ensemble',
        path: '/',
        permission: 'dashboard.view',
        icon: '◉',
      },
      {
        id: 'devices',
        label: 'Equipements',
        path: '/devices',
        permission: 'devices.view',
        icon: '⬡',
      },
      {
        id: 'agents',
        label: 'Agents',
        path: '/agents',
        permission: 'agents.view',
        icon: '◎',
      },
      {
        id: 'alerts',
        label: 'Alertes',
        path: '/alerts',
        permission: 'alerts.view',
        icon: '⚠',
      },
      {
        id: 'incidents',
        label: 'Incidents',
        path: '/incidents',
        permission: 'incidents.view',
        icon: '◈',
      },
      {
        id: 'topology',
        label: 'Topologie',
        path: '/topology',
        permission: 'topology.view',
        icon: '⬢',
      },
      {
        id: 'network',
        label: 'Reseau',
        path: '/network',
        permission: 'devices.view',
        icon: '↗',
      },
    ],
  },
  {
    label: 'Administration',
    items: [
      {
        id: 'users',
        label: 'Utilisateurs',
        path: '/users',
        permission: 'users.view',
        icon: '👤',
      },
    ],
  },
  {
    label: 'Rapports',
    items: [
      {
        id: 'reports',
        label: 'Rapports',
        path: '/reports',
        permission: 'reports.view',
        icon: '📄',
      },
    ],
  },
  {
    label: 'Aide',
    items: [
      {
        id: 'help',
        label: 'Aide',
        path: '/help',
        permission: null,
        icon: '?',
      },
    ],
  },
  {
    label: 'Intelligence',
    items: [
      {
        id: 'ai',
        label: 'ORION AI',
        path: '/ai',
        permission: 'ai.use',
        icon: '✦',
      },
    ],
  },
];
