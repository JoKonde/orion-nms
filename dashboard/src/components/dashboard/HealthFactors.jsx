/**
 * HealthFactors — detail des facteurs du score sante (endpoint /dashboard/health).
 */
export function HealthFactors({ factors }) {
  if (!factors) return null;

  const rows = [
    {
      label: 'Disponibilite equipements',
      value: `${factors.device_availability_pct ?? 0} %`,
      hint: 'Part des devices online',
    },
    {
      label: 'Disponibilite agents',
      value: `${factors.agent_availability_pct ?? 0} %`,
      hint: 'Agents online / total',
    },
    {
      label: 'Penalite alertes',
      value: `-${factors.alert_penalty ?? 0} pts`,
      hint: `${factors.active_alerts ?? 0} alerte(s) active(s)`,
    },
    {
      label: 'Penalite incidents',
      value: `-${factors.incident_penalty ?? 0} pts`,
      hint: `${factors.open_incidents ?? 0} incident(s) ouvert(s)`,
    },
  ];

  return (
    <div className="health-factors">
      <h3 className="health-factors__title">Facteurs de sante</h3>
      <ul className="health-factors__list">
        {rows.map((row) => (
          <li key={row.label} className="health-factors__item">
            <div>
              <strong>{row.label}</strong>
              <small>{row.hint}</small>
            </div>
            <span className="health-factors__value">{row.value}</span>
          </li>
        ))}
      </ul>
    </div>
  );
}
