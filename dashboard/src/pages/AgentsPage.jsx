import { useCallback, useEffect, useState } from 'react';
import * as agentsApi from '../api/agents';
import { PageHeader } from '../components/ui/PageHeader';
import { DataTable } from '../components/ui/DataTable';
import { Pagination } from '../components/ui/Pagination';
import { StatusBadge } from '../components/ui/StatusBadge';
import { Spinner } from '../components/ui/Spinner';
import { ErrorMessage } from '../components/ui/ErrorMessage';
import { ConfirmDialog } from '../components/ui/ConfirmDialog';
import { AgentDetailsModal } from '../components/agents/AgentDetailsModal';
import { usePermission } from '../hooks/usePermission';
import { useRealtimeRefresh } from '../hooks/useRealtimeRefresh';
import { AGENT_STATUSES } from '../utils/constants';
import { formatDate, formatLabel } from '../utils/format';

export function AgentsPage() {
  const canDelete = usePermission('agents.delete');

  const [items, setItems] = useState([]);
  const [meta, setMeta] = useState(null);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState(null);
  const [page, setPage] = useState(1);
  const [statusFilter, setStatusFilter] = useState('');
  const [deleteTarget, setDeleteTarget] = useState(null);
  const [detailsTarget, setDetailsTarget] = useState(null);

  const load = useCallback(async () => {
    setLoading(true);
    setError(null);
    try {
      const params = { page, per_page: 15 };
      if (statusFilter) params.status = statusFilter;
      const result = await agentsApi.fetchAgents(params);
      setItems(result.items);
      setMeta(result.meta);
    } catch (err) {
      setError(err.message);
    } finally {
      setLoading(false);
    }
  }, [page, statusFilter]);

  useEffect(() => {
    load();
  }, [load]);

  useRealtimeRefresh('agent.status.changed', load);

  const handleDelete = async () => {
    if (!deleteTarget) return;
    await agentsApi.deleteAgent(deleteTarget.id);
    setDeleteTarget(null);
    await load();
  };

  const columns = [
    { key: 'hostname', label: 'Hostname' },
    { key: 'os', label: 'OS', render: (r) => formatLabel(r.os) },
    { key: 'status', label: 'Statut', render: (r) => <StatusBadge value={r.status} /> },
    { key: 'agent_version', label: 'Version' },
    { key: 'device', label: 'Device', render: (r) => r.device?.name ?? '—' },
    { key: 'last_seen_at', label: 'Dernier contact', render: (r) => formatDate(r.last_seen_at) },
    {
      key: 'actions',
      label: 'Actions',
      render: (r) => (
        <div className="row-actions">
          <button type="button" className="btn btn--secondary btn--sm" onClick={() => setDetailsTarget(r)}>
            Details
          </button>
          {canDelete && (
            <button type="button" className="btn btn--danger btn--sm" onClick={() => setDeleteTarget(r)}>
              Suppr.
            </button>
          )}
        </div>
      ),
    },
  ];

  return (
    <div className="page crud-page">
      <PageHeader title="Agents ORION" subtitle="Agents deployes sur le parc" />

      <div className="filters-bar">
        <select value={statusFilter} onChange={(e) => { setPage(1); setStatusFilter(e.target.value); }}>
          <option value="">Tous statuts</option>
          {AGENT_STATUSES.map((s) => (
            <option key={s} value={s}>{formatLabel(s)}</option>
          ))}
        </select>
      </div>

      {loading && <Spinner />}
      {error && <ErrorMessage message={error} onRetry={load} />}
      {!loading && !error && (
        <>
          <DataTable columns={columns} rows={items} emptyMessage="Aucun agent enregistre." />
          <Pagination meta={meta} onPageChange={setPage} />
        </>
      )}

      <AgentDetailsModal agent={detailsTarget} onClose={() => setDetailsTarget(null)} />

      <ConfirmDialog
        open={!!deleteTarget}
        title="Supprimer l'agent"
        message={`Supprimer l'agent "${deleteTarget?.hostname}" et son device associe ?`}
        confirmLabel="Supprimer"
        danger
        onClose={() => setDeleteTarget(null)}
        onConfirm={handleDelete}
      />
    </div>
  );
}
