/** StatusBadge — pastille coloree selon statut/severite. */
const STATUS_VARIANTS = {
  online: 'success',
  offline: 'danger',
  unknown: 'muted',
  raised: 'danger',
  acknowledged: 'warning',
  resolved: 'success',
  open: 'danger',
  assigned: 'warning',
  in_progress: 'info',
  closed: 'muted',
  critical: 'danger',
  warning: 'warning',
  info: 'info',
  high: 'warning',
  medium: 'info',
  low: 'muted',
};

export function StatusBadge({ value }) {
  const variant = STATUS_VARIANTS[value] ?? 'default';
  const label = value ? String(value).replace(/_/g, ' ') : '—';

  return <span className={`status-badge status-badge--${variant}`}>{label}</span>;
}
