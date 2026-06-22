import { useAuth } from '../auth/AuthContext';
import { useDashboard } from '../hooks/useDashboard';
import { useRealtimeRefresh } from '../hooks/useRealtimeRefresh';
import { KpiCard } from '../components/dashboard/KpiCard';
import { HealthGauge } from '../components/dashboard/HealthGauge';
import { HealthFactors } from '../components/dashboard/HealthFactors';
import { Spinner } from '../components/ui/Spinner';
import { ErrorMessage } from '../components/ui/ErrorMessage';

/** Formate une date ISO en affichage local francais. */
function formatGeneratedAt(iso) {
  if (!iso) return '—';
  try {
    return new Date(iso).toLocaleString('fr-FR');
  } catch {
    return iso;
  }
}

/**
 * OverviewPage — accueil dashboard avec KPIs API (Module 12).
 */
export function OverviewPage() {
  const { user } = useAuth();
  const { overview, health, loading, error, refresh } = useDashboard();

  useRealtimeRefresh(
    ['alert.raised', 'incident.updated', 'agent.status.changed', 'topology.updated', 'device.discovered'],
    refresh,
  );

  if (loading) {
    return (
      <div className="page overview-page">
        <Spinner label="Chargement du tableau de bord..." />
      </div>
    );
  }

  if (error) {
    return (
      <div className="page overview-page">
        <ErrorMessage message={error} onRetry={refresh} />
      </div>
    );
  }

  const { devices, agents, alerts, incidents, topology, health: healthSummary } = overview ?? {};

  return (
    <div className="page overview-page">
      <div className="page__header overview-page__header">
        <div>
          <h2>Bienvenue, {user?.name}</h2>
          <p className="page__subtitle">
            Supervision reseau ORION — donnees mises a jour le{' '}
            {formatGeneratedAt(overview?.generated_at)}
          </p>
        </div>
        <button type="button" className="btn btn--secondary" onClick={refresh}>
          Actualiser
        </button>
      </div>

      <section className="overview-health-panel">
        <article className="card overview-health-panel__gauge">
          <h3 className="card__title">Sante reseau</h3>
          <HealthGauge score={healthSummary?.score ?? 0} grade={healthSummary?.grade ?? 'good'} />
        </article>
        <article className="card overview-health-panel__factors">
          <HealthFactors factors={health?.factors} />
        </article>
      </section>

      <section className="overview-kpi-grid">
        <KpiCard
          title="Equipements"
          value={devices?.total ?? 0}
          subtitle="Devices supervises"
          icon="⬡"
          linkTo="/devices"
          stats={[
            { label: 'Online', value: devices?.online ?? 0, variant: 'success' },
            { label: 'Offline', value: devices?.offline ?? 0, variant: 'danger' },
            { label: 'Inconnu', value: devices?.unknown ?? 0, variant: 'muted' },
          ]}
        />
        <KpiCard
          title="Agents"
          value={agents?.total ?? 0}
          subtitle="Agents ORION deployes"
          icon="◎"
          linkTo="/agents"
          stats={[
            { label: 'Online', value: agents?.online ?? 0, variant: 'success' },
            { label: 'Offline', value: agents?.offline ?? 0, variant: 'danger' },
          ]}
        />
        <KpiCard
          title="Alertes actives"
          value={alerts?.active ?? 0}
          subtitle="Alertes non resolues"
          icon="⚠"
          linkTo="/alerts"
          stats={[
            { label: 'Critical', value: alerts?.critical ?? 0, variant: 'danger' },
            { label: 'Warning', value: alerts?.warning ?? 0, variant: 'warning' },
          ]}
        />
        <KpiCard
          title="Incidents ouverts"
          value={incidents?.open ?? 0}
          subtitle="Incidents en cours"
          icon="◈"
          linkTo="/incidents"
          stats={[
            {
              label: 'Priorite critical',
              value: incidents?.critical_priority ?? 0,
              variant: 'danger',
            },
          ]}
        />
        <KpiCard
          title="Topologie"
          value={topology?.links ?? 0}
          subtitle="Liens reseau detectes"
          icon="⬢"
          linkTo="/topology"
        />
      </section>
    </div>
  );
}
