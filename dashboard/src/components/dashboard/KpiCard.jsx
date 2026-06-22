import { Link } from 'react-router-dom';
import { Badge } from '../ui/Badge';

/**
 * KpiCard — carte KPI pour la page Overview.
 * @param {string} title - Titre de la metrique
 * @param {number|string} value - Valeur principale
 * @param {string} [subtitle] - Sous-texte descriptif
 * @param {Array<{label: string, value: number|string, variant?: string}>} [stats] - Lignes detail
 * @param {string} [linkTo] - Lien vers page detail
 * @param {string} [icon] - Emoji / symbole
 */
export function KpiCard({ title, value, subtitle, stats = [], linkTo, icon }) {
  const content = (
    <>
      <div className="kpi-card__head">
        {icon && (
          <span className="kpi-card__icon" aria-hidden="true">
            {icon}
          </span>
        )}
        <h3 className="kpi-card__title">{title}</h3>
      </div>
      <p className="kpi-card__value">{value}</p>
      {subtitle && <p className="kpi-card__subtitle">{subtitle}</p>}
      {stats.length > 0 && (
        <ul className="kpi-card__stats">
          {stats.map((s) => (
            <li key={s.label}>
              <span>{s.label}</span>
              <Badge variant={s.variant ?? 'muted'}>{s.value}</Badge>
            </li>
          ))}
        </ul>
      )}
    </>
  );

  if (linkTo) {
    return (
      <Link to={linkTo} className="kpi-card kpi-card--link">
        {content}
      </Link>
    );
  }

  return <article className="kpi-card">{content}</article>;
}
