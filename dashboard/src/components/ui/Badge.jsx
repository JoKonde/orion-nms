/**
 * Badge — pastille de statut (severite, grade sante, compteurs).
 */
const VARIANTS = {
  default: 'badge--default',
  success: 'badge--success',
  warning: 'badge--warning',
  danger: 'badge--danger',
  info: 'badge--info',
  muted: 'badge--muted',
};

export function Badge({ children, variant = 'default', className = '' }) {
  const cls = ['badge', VARIANTS[variant] ?? VARIANTS.default, className]
    .filter(Boolean)
    .join(' ');

  return <span className={cls}>{children}</span>;
}

/** Map grade backend → variant Badge. */
export function healthGradeVariant(grade) {
  const map = {
    excellent: 'success',
    good: 'info',
    degraded: 'warning',
    poor: 'warning',
    critical: 'danger',
  };
  return map[grade] ?? 'default';
}

/** Libelle francais du grade sante. */
export function healthGradeLabel(grade) {
  const map = {
    excellent: 'Excellent',
    good: 'Bon',
    degraded: 'Degrade',
    poor: 'Faible',
    critical: 'Critique',
  };
  return map[grade] ?? grade;
}
