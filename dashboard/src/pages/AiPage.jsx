import { useCallback, useEffect, useState } from 'react';
import * as aiApi from '../api/ai';
import { PageHeader } from '../components/ui/PageHeader';
import { Spinner } from '../components/ui/Spinner';
import { ErrorMessage } from '../components/ui/ErrorMessage';
import { AiChat, AiInsightPanel } from '../components/ai/AiPanel';
import { usePermission } from '../hooks/usePermission';
import { useRealtimeRefresh } from '../hooks/useRealtimeRefresh';

export function AiPage() {
  const canUse = usePermission('ai.use');
  const [status, setStatus] = useState(null);
  const [insights, setInsights] = useState([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState(null);

  const load = useCallback(async () => {
    setLoading(true);
    setError(null);
    try {
      const [st, items] = await Promise.all([
        aiApi.fetchAiStatus(),
        aiApi.fetchAiInsights(15),
      ]);
      setStatus(st);
      setInsights(items);
    } catch (err) {
      setError(err.message);
    } finally {
      setLoading(false);
    }
  }, []);

  useEffect(() => {
    if (canUse) load();
  }, [canUse, load]);

  useRealtimeRefresh('ai.insight.created', load);

  if (!canUse) {
    return (
      <div className="page">
        <PageHeader title="ORION AI" subtitle="Permission requise (ai.use)" />
        <p>Contactez un administrateur pour activer l&apos;acces ORION AI.</p>
      </div>
    );
  }

  if (loading && !status) {
    return (
      <div className="page ai-page">
        <Spinner label="Chargement ORION AI..." />
      </div>
    );
  }

  if (error && !status) {
    return (
      <div className="page ai-page">
        <ErrorMessage message={error} onRetry={load} />
      </div>
    );
  }

  return (
    <div className="page ai-page">
      <PageHeader
        title="ORION AI"
        subtitle={
          status?.enabled
            ? `Copilote reseau — modele ${status.model}`
            : 'Desactive : verifiez OPENROUTER_API_KEY dans core/.env'
        }
      />

      {!status?.enabled && (
        <div className="ai-disabled-banner">
          ORION AI est desactive. Ajoutez votre cle OpenRouter dans <code>core/.env</code> puis{' '}
          <code>php artisan config:clear</code>.
        </div>
      )}

      <div className="ai-layout">
        <section className="card ai-layout__chat">
          <h2 className="card__title">Chat contextuel</h2>
          <p className="ai-hint">L&apos;IA recoit un instantane de votre reseau ORION a chaque question.</p>
          {status?.enabled ? (
            <AiChat />
          ) : (
            <p className="ai-insights-empty">Chat indisponible sans cle API.</p>
          )}
        </section>

        <aside className="card ai-layout__insights">
          <h2 className="card__title">Analyses recentes</h2>
          <p className="ai-hint">Inclut les analyses proactives (alertes critiques).</p>
          <div className="ai-layout__insights-scroll">
            <AiInsightPanel insights={insights} loading={loading} />
          </div>
        </aside>
      </div>
    </div>
  );
}
