import { Link } from 'react-router-dom';
import { PageHeader } from '../components/ui/PageHeader';

const MODULES = [
  {
    title: 'Vue d\'ensemble',
    path: '/',
    role: 'Tableau de bord principal : score de sante reseau, KPIs (equipements, agents, alertes, incidents). Point de depart pour voir si tout va bien.',
  },
  {
    title: 'Equipements',
    path: '/devices',
    role: 'Referentiel de tous les appareils decouverts (Nmap) ou enregistres (agent). Consulter IP, statut online/offline, modifier ou supprimer un device.',
  },
  {
    title: 'Agents',
    path: '/agents',
    role: 'PC supervises par l\'agent ORION (CPU, RAM, disque). Voir le statut, les metriques (bouton Details) et supprimer un agent obsolete.',
  },
  {
    title: 'Alertes',
    path: '/alerts',
    role: 'Signaux automatiques declenches par des regles (seuil CPU, machine hors ligne…). A traiter rapidement : acquitter, resoudre ou escalader en incident.',
  },
  {
    title: 'Incidents',
    path: '/incidents',
    role: 'Tickets de prise en charge humaine : investigation, assignation, resolution et cloture. Un incident peut naitre d\'une alerte ou etre cree manuellement.',
  },
  {
    title: 'Topologie',
    path: '/topology',
    role: 'Carte visuelle du reseau (noeuds et liens). Utile pour localiser un equipement isole ou voir la structure autour du routeur.',
  },
  {
    title: 'Reseau',
    path: '/network',
    role: 'Detection du sous-reseau local et lancement du scan Nmap en ligne de commande pour decouvrir de nouveaux equipements.',
  },
  {
    title: 'Utilisateurs',
    path: '/users',
    role: 'Gestion des comptes admin, operateur et lecteur. Reserve aux administrateurs.',
  },
  {
    title: 'ORION AI',
    path: '/ai',
    role: 'Copilote IA : chat contextuel sur le reseau, analyse d\'alertes/incidents, analyses proactives sur alertes critiques.',
  },
  {
    title: 'Rapports',
    path: '/reports',
    role: 'Exports CSV et rapports HTML : synthese reseau, inventaires, alertes et incidents sur une periode.',
  },
];

const AI_FEATURES = [
  {
    level: 'Niveau 1 — Chat',
    text: 'Posez des questions en langage naturel (« Quelles alertes sont ouvertes ? », « Quels equipements sont offline ? »). L\'IA recoit un instantane du reseau ORION a chaque message.',
  },
  {
    level: 'Niveau 2 — Analyse a la demande',
    text: 'Bouton IA sur les pages Alertes et Incidents : resume, causes probables et plan d\'intervention pour l\'element selectionne.',
  },
  {
    level: 'Niveau 3 — Proactif',
    text: 'Chaque alerte critique declenche automatiquement une analyse IA. Le resultat apparait dans Analyses recentes et en notification temps reel.',
  },
];

const AI_SETUP_LINES = [
  'ORION_AI_ENABLED=true',
  'OPENROUTER_API_KEY=votre_cle',
  'OPENROUTER_MODEL=openrouter/free',
  'OPENROUTER_BASE_URL=https://openrouter.ai/api/v1',
];

const INCIDENT_STEPS = [
  { status: 'open', action: 'Incident cree (manuel ou depuis une alerte). Assignez-le a un operateur.' },
  { status: 'assigned', action: 'Un responsable est designe. Cliquez sur Demarrer pour passer en traitement.' },
  { status: 'in_progress', action: 'Intervention en cours sur le terrain ou a distance. Resoudre quand le probleme est corrige.' },
  { status: 'resolved', action: 'Probleme corrige. Clore l\'incident pour retirer la penalite sur le score sante.' },
  { status: 'closed', action: 'Incident archive. Suppression possible si besoin.' },
];

export function HelpPage() {
  return (
    <div className="page help-page">
      <PageHeader
        title="Aide ORION"
        subtitle="Comprendre les outils et intervenir sur le reseau"
      />

      <article className="card help-block">
        <h2>Alerte ou incident : quelle difference ?</h2>
        <div className="help-compare">
          <div className="help-compare__col">
            <h3>Alerte</h3>
            <ul>
              <li><strong>Automatique</strong> — declenchee par une regle (ex. CPU &gt; 90 %, PC offline).</li>
              <li><strong>Legere et rapide</strong> — signale un symptome, pas encore un dossier de travail.</li>
              <li><strong>Statuts</strong> : levee → acquittee → resolue.</li>
              <li><strong>Objectif</strong> : informer et reagir vite.</li>
            </ul>
          </div>
          <div className="help-compare__col">
            <h3>Incident</h3>
            <ul>
              <li><strong>Humain</strong> — ticket de prise en charge avec suivi.</li>
              <li><strong>Structure</strong> — priorite, assignation, notes de resolution.</li>
              <li><strong>Statuts</strong> : ouvert → assigne → en cours → resolu → cloture.</li>
              <li><strong>Objectif</strong> : traiter et documenter un probleme jusqu\'au bout.</li>
            </ul>
          </div>
        </div>
        <p className="help-note">
          Une alerte <strong>critique</strong> peut creer automatiquement un incident.
          Sinon, utilisez le bouton <strong>Escalader</strong> sur la page{' '}
          <Link to="/alerts">Alertes</Link>.
        </p>
      </article>

      <article className="card help-block">
        <h2>Resoudre une alerte (admin / operateur)</h2>
        <ol className="help-steps">
          <li>
            Ouvrez <Link to="/alerts">Alertes</Link> → onglet <em>Alertes declenchees</em>.
          </li>
          <li>
            Identifiez l&apos;equipement concerne et la severite (info, warning, critical).
          </li>
          <li>
            <strong>Acquitter</strong> : « j&apos;ai vu l&apos;alerte, je m&apos;en occupe » (statut acknowledged).
          </li>
          <li>
            Corrigez la cause : redemarrer un service, liberer de la RAM, reconnecter le PC, etc.
            Consultez <Link to="/devices">Equipements</Link> ou <Link to="/agents">Agents</Link> → Details pour les metriques.
          </li>
          <li>
            <strong>Resoudre</strong> l&apos;alerte une fois le probleme corrige.
          </li>
          <li>
            Si le probleme est complexe : <strong>Escalader</strong> vers un incident (redirection automatique).
          </li>
        </ol>
        <p className="help-note">
          Regles d&apos;alerte : meme page, onglet <em>Regles d&apos;alerte</em> — seuils CPU/RAM ou detection offline.
        </p>
      </article>

      <article className="card help-block">
        <h2>ORION AI — copilote reseau</h2>
        <p className="help-intro">
          ORION AI (Module 10) utilise OpenRouter pour analyser votre supervision et proposer des
          diagnostics lisibles. Accessible via <Link to="/ai">Intelligence → ORION AI</Link>.
        </p>

        <div className="help-ai-levels">
          {AI_FEATURES.map((feat) => (
            <div key={feat.level} className="help-ai-levels__item">
              <h3>{feat.level}</h3>
              <p>{feat.text}</p>
            </div>
          ))}
        </div>

        <h3>Utilisation au quotidien</h3>
        <ol className="help-steps">
          <li>
            Ouvrez <Link to="/ai">ORION AI</Link> — chat a gauche, <strong>Analyses recentes</strong> a
            droite (liste scrollable).
          </li>
          <li>
            Posez une question dans le chat : l&apos;IA connait alertes, incidents, equipements et score
            de sante du moment.
          </li>
          <li>
            Sur <Link to="/alerts">Alertes</Link> ou <Link to="/incidents">Incidents</Link>, cliquez{' '}
            <strong>IA</strong> sur une ligne pour une analyse detaillee (resume, causes, plan pas a pas).
          </li>
          <li>
            Les alertes <strong>critiques</strong> generent une analyse automatique sans action de votre
            part — consultez le panneau de droite ou les notifications.
          </li>
        </ol>

        <p className="help-note">
          Les reponses sont formatees en <strong>Markdown</strong> (titres, gras, listes) pour une
          lecture claire. Exemple : <code>### Resume</code> devient un titre, <code>**texte**</code> du
          gras.
        </p>

        <h3>Configuration (administrateur)</h3>
        <p className="help-text-muted">
          La cle API reste dans <code>core/.env</code> — jamais exposee au navigateur. Apres modification :
        </p>
        <pre className="help-code">{AI_SETUP_LINES.join('\n')}</pre>
        <p className="help-text-muted">
          Puis <code>php artisan config:clear</code>. Modele recommande en gratuit :{' '}
          <code>openrouter/free</code> (routage automatique vers un modele disponible).
        </p>

        <p className="help-note">
          Permission requise : <strong>ai.use</strong> (administrateur et operateur par defaut). Les lecteurs
          n&apos;ont pas acces au module.
        </p>
      </article>

      <article className="card help-block">
        <h2>Resoudre un incident</h2>
        <ol className="help-steps">
          <li>
            Ouvrez <Link to="/incidents">Incidents</Link> ou cliquez sur une notification.
          </li>
          <li>
            <strong>Detail</strong> : lisez la description et l&apos;equipement lie.
          </li>
          <li>
            <strong>M&apos;assigner</strong> ou choisissez un operateur dans la liste.
          </li>
          <li>
            <strong>Demarrer</strong> quand l&apos;intervention commence.
          </li>
          <li>
            Diagnostiquez via <Link to="/topology">Topologie</Link>, metriques agent, scan{' '}
            <Link to="/network">Reseau</Link> ou <Link to="/ai">ORION AI</Link> (bouton IA / chat).
          </li>
          <li>
            <strong>Resoudre</strong> apres correction (notes enregistrees cote serveur).
          </li>
          <li>
            <strong>Clore</strong> pour finaliser et ameliorer le score sante.
          </li>
        </ol>
        <div className="help-timeline">
          {INCIDENT_STEPS.map((step) => (
            <div key={step.status} className="help-timeline__item">
              <span className="help-timeline__badge">{step.status.replace('_', ' ')}</span>
              <p>{step.action}</p>
            </div>
          ))}
        </div>
      </article>

      <article className="card help-block">
        <h2>Procedure type : un probleme sur le reseau</h2>
        <ol className="help-steps help-steps--numbered">
          <li>
            <strong>Vue d&apos;ensemble</strong> — verifier le score sante et les KPIs alertes/incidents.
          </li>
          <li>
            <strong>Alertes actives</strong> — traiter les critical en priorite (analyse IA auto si activee).
          </li>
          <li>
            <strong>Equipement / Agent</strong> — confirmer online, IP correcte, metriques CPU/RAM.
          </li>
          <li>
            <strong>Topologie</strong> — le device est-il relie au routeur ou isole ?
          </li>
          <li>
            <strong>Reseau</strong> — relancer un scan Nmap si un poste manque.
          </li>
          <li>
            <strong>Incident</strong> — escalader, assigner, resoudre, cloturer.
          </li>
          <li>
            <strong>Verification</strong> — alerte resolue, incident cloture, score sante en hausse.
          </li>
        </ol>
      </article>

      <article className="card help-block">
        <h2>A quoi sert chaque menu ?</h2>
        <ul className="help-modules">
          {MODULES.map((mod) => (
            <li key={mod.path} className="help-modules__item">
              <Link to={mod.path} className="help-modules__title">
                {mod.title}
                {mod.soon && <span className="help-modules__soon">Soon</span>}
              </Link>
              <p>{mod.role}</p>
            </li>
          ))}
        </ul>
      </article>

      <article className="card help-block help-block--muted">
        <h2>Roles</h2>
        <ul className="help-list">
          <li><strong>Administrateur</strong> — tout : utilisateurs, regles d&apos;alerte, suppression.</li>
          <li><strong>Operateur</strong> — supervision, alertes, incidents, ORION AI, pas la gestion utilisateurs.</li>
          <li><strong>Lecteur</strong> — consultation seule, pas d&apos;actions de resolution ni ORION AI.</li>
        </ul>
      </article>
    </div>
  );
}
