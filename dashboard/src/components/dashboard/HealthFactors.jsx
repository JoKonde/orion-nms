/**
 * HealthFactors — detail des facteurs du score sante (endpoint /dashboard/health).
 */
export function HealthFactors({ factors }) {
  if (!factors) {
    return (
      <div className="health-factors">
        <h3 className="health-factors__title">Facteurs de sante</h3>
        <p className="health-factors__empty">Chargement des indicateurs…</p>
      </div>
    );
  }

  const devicePct = factors.device_availability_pct ?? 0;
  const agentPct = factors.agent_availability_pct ?? 0;
  const alertPts = factors.alert_penalty ?? 0;
  const incidentPts = factors.incident_penalty ?? 0;

  const rows = [
    {
      label: 'Disponibilite equipements',
      value: `${devicePct} %`,
      hint: 'Part des devices online (poids : 55 % du score)',
      contribution: `+${Math.round(devicePct * 0.55)} pts env.`,
      positive: true,
    },
    {
      label: 'Disponibilite agents',
      value: `${agentPct} %`,
      hint: 'Agents qui repondent au heartbeat (poids : 15 %)',
      contribution: `+${Math.round(agentPct * 0.15)} pts env.`,
      positive: true,
    },
    {
      label: 'Penalite alertes',
      value: `−${alertPts} pts`,
      hint: `${factors.active_alerts ?? 0} alerte(s) active(s) — max. −35 pts`,
      contribution: 'Retire du score',
      positive: false,
    },
    {
      label: 'Penalite incidents',
      value: `−${incidentPts} pts`,
      hint: `${factors.open_incidents ?? 0} incident(s) ouvert(s) — max. −25 pts`,
      contribution: 'Retire du score',
      positive: false,
    },
  ];

  return (
    <div className="health-factors">
      <h3 className="health-factors__title">Facteurs de sante</h3>
      <p className="health-factors__lead">
        Chaque ligne influence le score sur 100. Les % augmentent la note ; les «&nbsp;pts&nbsp;»
        en penalite la diminuent.
      </p>
      <ul className="health-factors__list">
        {rows.map((row) => (
          <li key={row.label} className="health-factors__item">
            <div>
              <strong>{row.label}</strong>
              <small>{row.hint}</small>
              <span className={`health-factors__contrib health-factors__contrib--${row.positive ? 'up' : 'down'}`}>
                {row.contribution}
              </span>
            </div>
            <span className="health-factors__value">{row.value}</span>
          </li>
        ))}
      </ul>
    </div>
  );
}
