export const DEVICE_TYPES = [
  'router',
  'switch',
  'firewall',
  'printer',
  'access_point',
  'pc',
  'other',
];

export const DEVICE_STATUSES = ['online', 'offline', 'unknown'];

export const ALERT_STATUSES = ['raised', 'acknowledged', 'resolved'];

export const ALERT_SEVERITIES = ['info', 'warning', 'critical'];

export const ALERT_RULE_TYPES = ['metric_threshold', 'device_offline'];

export const METRIC_TYPES = ['cpu', 'ram', 'disk', 'disk_usage', 'temperature', 'network_in', 'network_out', 'uptime'];

export const ALERT_OPERATORS = ['gt', 'gte', 'lt', 'lte'];

export const INCIDENT_STATUSES = ['open', 'assigned', 'in_progress', 'resolved', 'closed'];

export const INCIDENT_PRIORITIES = ['low', 'medium', 'high', 'critical'];

export const AGENT_STATUSES = ['online', 'offline'];

/** Roles dashboard ORION (alignes sur RoleName backend). */
export const USER_ROLES = [
  {
    value: 'admin',
    label: 'Administrateur',
    description: 'Acces total : utilisateurs, configuration et toutes les actions.',
  },
  {
    value: 'operator',
    label: 'Operateur',
    description: 'Supervision reseau : equipements, alertes, incidents et topologie.',
  },
  {
    value: 'viewer',
    label: 'Lecteur',
    description: 'Consultation du dashboard uniquement (lecture seule).',
  },
];
