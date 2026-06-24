import { healthGradeLabel } from '../ui/Badge';

const GRADE_LEVELS = [
  { range: '90 – 100', grade: 'excellent', label: 'Excellent', desc: 'Reseau tres sain, peu ou pas de problemes.' },
  { range: '75 – 89', grade: 'good', label: 'Bon', desc: 'Situation normale, surveillance de routine.' },
  { range: '50 – 74', grade: 'degraded', label: 'Degrade', desc: 'Quelques soucis a traiter prochainement.' },
  { range: '25 – 49', grade: 'poor', label: 'Faible', desc: 'Plusieurs equipements ou alertes necessitent action.' },
  { range: '0 – 24', grade: 'critical', label: 'Critique', desc: 'Etat preoccupant : intervention recommandee.' },
];

/**
 * Guide utilisateur — comment lire et ameliorer le score sante reseau.
 */
export function HealthGuide({ score = 0, grade = 'good', factors }) {
  const devicePct = factors?.device_availability_pct ?? 0;
  const agentPct = factors?.agent_availability_pct ?? 0;
  const alertPts = factors?.alert_penalty ?? 0;
  const incidentPts = factors?.incident_penalty ?? 0;

  const devicePart = Math.round(devicePct * 0.55);
  const agentPart = Math.round(agentPct * 0.15);
  const estimated = Math.max(0, Math.min(100, devicePart + agentPart - alertPts - incidentPts));

  return (
    <article className="card health-guide">
      <h3 className="card__title">Comprendre la sante reseau</h3>

      <div className="health-guide__intro">
        <p>
          Le <strong>score sur 100</strong> resume l&apos;etat global de votre infrastructure :
          equipements joignables, agents actifs, et impact des alertes / incidents en cours.
          Plus il est eleve, mieux c&apos;est.
        </p>
        <p className="health-guide__current">
          Votre score actuel : <strong>{score} / 100</strong> —{' '}
          <strong>{healthGradeLabel(grade)}</strong>
          {score === 0 && (
            <span className="health-guide__note">
              {' '}
              (un score a 0 signifie que les penalites et les equipements hors ligne
              l&apos;emportent sur la partie positive ; le chiffre s&apos;affiche bien,
              le cercle coloré est simplement vide.)
            </span>
          )}
        </p>
      </div>

      <section className="health-guide__block">
        <h4>Calcul simplifie</h4>
        <p className="health-guide__formula">
          Score ≈ (disponibilite equipements × 55&nbsp;%) + (disponibilite agents × 15&nbsp;%)
          − penalite alertes − penalite incidents
        </p>
        {factors && (
          <p className="health-guide__example mono">
            ≈ ({devicePct} × 0,55) + ({agentPct} × 0,15) − {alertPts} − {incidentPts}
            {' '}= <strong>{estimated} / 100</strong>
          </p>
        )}
        <ul className="health-guide__list">
          <li>
            <strong>Disponibilite equipements (%)</strong> — part des machines marquees{' '}
            <em>online</em> parmi celles connues (hors statut inconnu). Provient du scan Nmap,
            du ping ou de l&apos;agent installe sur le PC.
          </li>
          <li>
            <strong>Disponibilite agents (%)</strong> — agents ORION qui envoient encore un
            signe de vie (heartbeat). Si vous n&apos;avez aucun agent, cette partie compte
            comme 100&nbsp;%.
          </li>
          <li>
            <strong>Penalite alertes (points)</strong> — malus jusqu&apos;a <strong>−35 pts</strong>{' '}
            selon les alertes <em>actives</em> (non resolues). Plus il y a d&apos;alertes
            critiques, plus la penalite monte vite.
          </li>
          <li>
            <strong>Penalite incidents (points)</strong> — malus jusqu&apos;a <strong>−25 pts</strong>{' '}
            selon les incidents encore <em>ouverts</em> (non clotures).
          </li>
        </ul>
        <p className="health-guide__hint">
          Les «&nbsp;pts&nbsp;» ne sont pas un deuxieme score : ce sont des points retires du
          score principal, comme une amende sur une note.
        </p>
      </section>

      <section className="health-guide__block">
        <h4>Niveaux de sante</h4>
        <ul className="health-guide__grades">
          {GRADE_LEVELS.map((row) => (
            <li key={row.grade}>
              <span className="health-guide__grade-range">{row.range}</span>
              <strong>{row.label}</strong>
              <span>{row.desc}</span>
            </li>
          ))}
        </ul>
      </section>

      <section className="health-guide__block">
        <h4>Comment ameliorer votre score</h4>
        <ol className="health-guide__tips">
          <li>
            <strong>Remettre des equipements en ligne</strong> — PC allumes, agent ORION actif
            ou scan reseau a jour (CLI : <code>php artisan orion:network-detect --scan</code>).
          </li>
          <li>
            <strong>Traiter les alertes</strong> — acquitter ou resoudre celles qui ne sont plus
            pertinentes (page Alertes).
          </li>
          <li>
            <strong>Cloturer les incidents</strong> — fermer les tickets resolus (page Incidents).
          </li>
          <li>
            <strong>Deployer l&apos;agent ORION</strong> sur les postes importants pour une
            supervision fiable (CPU, RAM) et un meilleur statut online.
          </li>
        </ol>
      </section>
    </article>
  );
}
