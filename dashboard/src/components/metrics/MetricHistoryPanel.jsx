import { useCallback, useEffect, useState } from 'react';
import * as metricsApi from '../../api/metrics';
import { Spinner } from '../ui/Spinner';
import { MetricLineChart } from './MetricLineChart';

const RANGES = [
  { id: '24h', label: '24 h' },
  { id: '7d', label: '7 jours' },
  { id: '30d', label: '30 jours' },
];

export function MetricHistoryPanel({ deviceId }) {
  const [range, setRange] = useState('24h');
  const [series, setSeries] = useState({});
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState(null);

  const load = useCallback(async () => {
    if (!deviceId) return;

    setLoading(true);
    setError(null);

    try {
      const results = await Promise.all(
        metricsApi.CHART_METRICS.map(async (metric) => {
          const history = await metricsApi.fetchDeviceMetricHistory(deviceId, metric.type, range);
          return [metric.type, history.points];
        }),
      );
      setSeries(Object.fromEntries(results));
    } catch (err) {
      setError(err.message || 'Impossible de charger l\'historique.');
      setSeries({});
    } finally {
      setLoading(false);
    }
  }, [deviceId, range]);

  useEffect(() => {
    load();
  }, [load]);

  const hasAnyPoint = metricsApi.CHART_METRICS.some((m) => (series[m.type]?.length ?? 0) > 0);

  return (
    <section className="metric-history">
      <div className="metric-history__head">
        <h3 className="agent-metrics__title">Historique</h3>
        <div className="metric-history__ranges">
          {RANGES.map((r) => (
            <button
              key={r.id}
              type="button"
              className={`metric-history__range${range === r.id ? ' metric-history__range--active' : ''}`}
              onClick={() => setRange(r.id)}
              disabled={loading}
            >
              {r.label}
            </button>
          ))}
        </div>
      </div>

      {loading && <Spinner label="Chargement des graphiques..." />}
      {error && <p className="agent-metrics__error">{error}</p>}

      {!loading && !error && (
        <div className="metric-history__grid">
          {metricsApi.CHART_METRICS.map((metric) => (
            <article key={metric.type} className="metric-history__card">
              <h4 className="metric-history__card-title">{metric.label}</h4>
              <MetricLineChart
                label={metric.label}
                unit={metric.unit}
                color={metric.color}
                points={series[metric.type] ?? []}
                maxY={metric.maxY}
                range={range}
              />
            </article>
          ))}
        </div>
      )}

      {!loading && !error && !hasAnyPoint && (
        <p className="agent-metrics__hint">
          Pas encore assez de mesures pour tracer les graphiques. L&apos;agent envoie des donnees
          toutes les 60 secondes.
        </p>
      )}
    </section>
  );
}
