import { useEffect, useState } from 'react';
import { Modal } from '../ui/Modal';
import { Spinner } from '../ui/Spinner';
import { StatusBadge } from '../ui/StatusBadge';
import * as metricsApi from '../../api/metrics';
import { MetricHistoryPanel } from '../metrics/MetricHistoryPanel';
import { formatDate, formatLabel } from '../../utils/format';

function formatPercent(value) {
  if (value == null) return '—';
  return `${Number(value).toFixed(1)} %`;
}

function formatGb(value) {
  if (value == null || value <= 0) return '—';
  return `${Number(value).toFixed(1)} Go`;
}

function formatMbps(value) {
  if (value == null || value < 0) return '—';
  return `${Number(value).toFixed(2)} Mbit/s`;
}

function formatCelsius(value) {
  if (value == null || value <= 0) return '—';
  return `${Number(value).toFixed(1)} °C`;
}

function formatUptime(seconds) {
  if (seconds == null) return '—';
  const h = Math.floor(seconds / 3600);
  const m = Math.floor((seconds % 3600) / 60);
  return `${h} h ${m} min`;
}

function computeUsedFromTotal(totalGb, usagePercent) {
  if (totalGb == null || usagePercent == null || totalGb <= 0) return null;
  return totalGb * (usagePercent / 100);
}

function computeFreeFromTotal(totalGb, usagePercent) {
  const used = computeUsedFromTotal(totalGb, usagePercent);
  if (totalGb == null || used == null) return null;
  return totalGb - used;
}

const METRIC_KEYS = [
  'cpu',
  'ram',
  'ram_total',
  'swap_usage',
  'disk',
  'disk_usage',
  'network_in',
  'network_out',
  'temperature',
  'uptime',
];

function hasAnyMetric(metrics) {
  if (!metrics) return false;
  return METRIC_KEYS.some((key) => metrics[key] != null);
}

function MetricSection({ title, children }) {
  return (
    <section className="agent-metrics-section">
      <h4 className="agent-metrics-section__title">{title}</h4>
      <div className="agent-metrics-grid">{children}</div>
    </section>
  );
}

function MetricCard({ label, value, small }) {
  return (
    <div className="agent-metric-card">
      <span className="agent-metric-card__label">{label}</span>
      <strong className={`agent-metric-card__value${small ? ' agent-metric-card__value--sm' : ''}`}>
        {value}
      </strong>
    </div>
  );
}

export function AgentDetailsModal({ agent, onClose }) {
  const [metrics, setMetrics] = useState(null);
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState(null);

  const deviceId = agent?.device?.id;

  useEffect(() => {
    if (!agent || !deviceId) return;

    let cancelled = false;
    setLoading(true);
    setError(null);

    metricsApi
      .fetchLatestDeviceMetrics(deviceId)
      .then((data) => {
        if (!cancelled) setMetrics(data);
      })
      .catch((err) => {
        if (!cancelled) setError(err.message || 'Impossible de charger les metriques.');
      })
      .finally(() => {
        if (!cancelled) setLoading(false);
      });

    return () => {
      cancelled = true;
    };
  }, [agent, deviceId]);

  if (!agent) return null;

  const diskTotal = metrics?.disk?.value;
  const diskUsage = metrics?.disk_usage?.value;
  const diskFree = computeFreeFromTotal(diskTotal, diskUsage);
  const diskUsed = computeUsedFromTotal(diskTotal, diskUsage);

  const ramTotal = metrics?.ram_total?.value;
  const ramUsage = metrics?.ram?.value;
  const ramUsed = computeUsedFromTotal(ramTotal, ramUsage);
  const ramFree = computeFreeFromTotal(ramTotal, ramUsage);

  return (
    <Modal open={!!agent} title={`Agent — ${agent.hostname}`} onClose={onClose} charts>
      <dl className="detail-list">
        <dt>Statut</dt>
        <dd><StatusBadge value={agent.status} /></dd>
        <dt>OS</dt>
        <dd>{formatLabel(agent.os)}</dd>
        <dt>Version agent</dt>
        <dd>{agent.agent_version || '—'}</dd>
        <dt>Device</dt>
        <dd>{agent.device?.name || '—'}</dd>
        <dt>IP</dt>
        <dd>{agent.device?.ip_address || '—'}</dd>
        <dt>Enregistre le</dt>
        <dd>{formatDate(agent.registered_at)}</dd>
        <dt>Dernier contact</dt>
        <dd>{formatDate(agent.last_seen_at)}</dd>
      </dl>

      <h3 className="agent-metrics__title">Metriques (derniere sync)</h3>

      {loading && <Spinner />}
      {error && <p className="agent-metrics__error">{error}</p>}

      {!loading && !error && (
        <div className="agent-metrics-sections">
          <MetricSection title="Processeur & systeme">
            <MetricCard label="CPU" value={formatPercent(metrics?.cpu?.value)} />
            <MetricCard label="Temperature CPU" value={formatCelsius(metrics?.temperature?.value)} />
            <MetricCard
              label="Uptime"
              value={formatUptime(metrics?.uptime?.value)}
              small
            />
          </MetricSection>

          <MetricSection title="Memoire">
            <MetricCard label="RAM utilisee" value={formatPercent(ramUsage)} />
            <MetricCard label="RAM totale" value={formatGb(ramTotal)} />
            <MetricCard label="RAM utilisee (Go)" value={formatGb(ramUsed)} />
            <MetricCard label="RAM libre" value={formatGb(ramFree)} />
            <MetricCard label="Swap utilise" value={formatPercent(metrics?.swap_usage?.value)} />
          </MetricSection>

          <MetricSection title="Disque">
            <MetricCard label="Disque total" value={formatGb(diskTotal)} />
            <MetricCard label="Espace utilise" value={formatGb(diskUsed)} />
            <MetricCard label="Espace libre" value={formatGb(diskFree)} />
            <MetricCard label="Utilisation disque" value={formatPercent(diskUsage)} />
          </MetricSection>

          <MetricSection title="Reseau">
            <MetricCard label="Debit entrant" value={formatMbps(metrics?.network_in?.value)} />
            <MetricCard label="Debit sortant" value={formatMbps(metrics?.network_out?.value)} />
          </MetricSection>
        </div>
      )}

      {!loading && !error && !hasAnyMetric(metrics) && (
        <p className="agent-metrics__hint">
          Aucune metrique recue pour cet equipement. Verifiez que l&apos;agent est actif
          (synchronisation toutes les 60 s).
        </p>
      )}

      {deviceId && <MetricHistoryPanel deviceId={deviceId} />}
    </Modal>
  );
}
