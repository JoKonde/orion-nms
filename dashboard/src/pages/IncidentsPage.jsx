import { useCallback, useEffect, useState } from 'react';
import { useSearchParams } from 'react-router-dom';
import * as incidentsApi from '../api/incidents';
import { fetchUsers } from '../api/users';
import { PageHeader } from '../components/ui/PageHeader';
import { DataTable } from '../components/ui/DataTable';
import { Pagination } from '../components/ui/Pagination';
import { StatusBadge } from '../components/ui/StatusBadge';
import { Spinner } from '../components/ui/Spinner';
import { ErrorMessage } from '../components/ui/ErrorMessage';
import { ConfirmDialog } from '../components/ui/ConfirmDialog';
import { IncidentFormModal } from '../components/incidents/IncidentFormModal';
import { AiAnalyzeModal, useAiAnalyze } from '../components/ai/AiPanel';
import { Modal } from '../components/ui/Modal';
import { useAuth } from '../auth/AuthContext';
import { usePermission } from '../hooks/usePermission';
import { INCIDENT_STATUSES, INCIDENT_PRIORITIES } from '../utils/constants';
import { formatDate, formatLabel } from '../utils/format';

export function IncidentsPage() {
  const { user } = useAuth();
  const canCreate = usePermission('incidents.create');
  const canUpdate = usePermission('incidents.update');
  const canAssign = usePermission('incidents.assign');
  const canClose = usePermission('incidents.close');
  const canDelete = usePermission('incidents.update'); // pas de permission delete dediee viewer
  const canUseAi = usePermission('ai.use');
  const ai = useAiAnalyze();

  const [searchParams] = useSearchParams();
  const highlightId = searchParams.get('highlight');

  const [items, setItems] = useState([]);
  const [meta, setMeta] = useState(null);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState(null);
  const [page, setPage] = useState(1);
  const [filters, setFilters] = useState({ status: '', priority: '' });

  const [modalOpen, setModalOpen] = useState(false);
  const [saving, setSaving] = useState(false);
  const [deleteTarget, setDeleteTarget] = useState(null);
  const [detailTarget, setDetailTarget] = useState(null);
  const [users, setUsers] = useState([]);
  const [actionLoading, setActionLoading] = useState(null);

  const load = useCallback(async () => {
    setLoading(true);
    setError(null);
    try {
      const params = { page, per_page: 15 };
      if (filters.status) params.status = filters.status;
      if (filters.priority) params.priority = filters.priority;
      const result = await incidentsApi.fetchIncidents(params);
      setItems(result.items);
      setMeta(result.meta);
    } catch (err) {
      setError(err.message);
    } finally {
      setLoading(false);
    }
  }, [page, filters]);

  useEffect(() => {
    load();
  }, [load]);

  useEffect(() => {
    if (canAssign) {
      fetchUsers().then((r) => setUsers(r.items)).catch(() => {});
    }
  }, [canAssign]);

  useEffect(() => {
    if (highlightId && items.length) {
      const found = items.find((i) => String(i.id) === highlightId);
      if (found) setDetailTarget(found);
    }
  }, [highlightId, items]);

  const handleCreate = async (payload) => {
    setSaving(true);
    try {
      await incidentsApi.createIncident(payload);
      await load();
    } finally {
      setSaving(false);
    }
  };

  const handleDelete = async () => {
    if (!deleteTarget) return;
    await incidentsApi.deleteIncident(deleteTarget.id);
    setDeleteTarget(null);
    await load();
  };

  const runTransition = async (incident, action, extra) => {
    setActionLoading(incident.id);
    try {
      if (action === 'assign') {
        await incidentsApi.assignIncident(incident.id, extra ?? user.id);
      } else if (action === 'start') {
        await incidentsApi.startIncident(incident.id);
      } else if (action === 'resolve') {
        await incidentsApi.resolveIncident(incident.id, extra);
      } else if (action === 'close') {
        await incidentsApi.closeIncident(incident.id);
      }
      await load();
      if (detailTarget?.id === incident.id) {
        const fresh = await incidentsApi.fetchIncident(incident.id);
        setDetailTarget(fresh);
      }
    } catch (err) {
      setError(err.message);
    } finally {
      setActionLoading(null);
    }
  };

  const openDetail = async (incident) => {
    try {
      const full = await incidentsApi.fetchIncident(incident.id);
      setDetailTarget(full);
    } catch {
      setDetailTarget(incident);
    }
  };

  const columns = [
    { key: 'title', label: 'Titre' },
    { key: 'priority', label: 'Priorite', render: (r) => <StatusBadge value={r.priority} /> },
    { key: 'status', label: 'Statut', render: (r) => <StatusBadge value={r.status} /> },
    { key: 'device', label: 'Equipement', render: (r) => r.device?.name ?? '—' },
    { key: 'assignee', label: 'Assigne a', render: (r) => r.assignee?.name ?? '—' },
    { key: 'opened_at', label: 'Ouvert le', render: (r) => formatDate(r.opened_at) },
    {
      key: 'actions',
      label: 'Actions',
      render: (r) => (
        <div className="row-actions">
          {canUseAi && !['closed'].includes(r.status) && (
            <button
              type="button"
              className="btn btn--sm ai-btn-analyze"
              onClick={() => ai.runIncident(r)}
            >
              IA
            </button>
          )}
          <button type="button" className="btn btn--secondary btn--sm" onClick={() => openDetail(r)}>
            Detail
          </button>
          {canDelete && r.status === 'closed' && (
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
      <PageHeader
        title="Incidents"
        subtitle="Gestion des incidents reseau"
        actions={
          canCreate && (
            <button type="button" className="btn btn--primary" onClick={() => setModalOpen(true)}>
              + Nouvel incident
            </button>
          )
        }
      />

      <div className="filters-bar">
        <select
          value={filters.status}
          onChange={(e) => { setPage(1); setFilters((f) => ({ ...f, status: e.target.value })); }}
        >
          <option value="">Tous statuts</option>
          {INCIDENT_STATUSES.map((s) => (
            <option key={s} value={s}>{formatLabel(s)}</option>
          ))}
        </select>
        <select
          value={filters.priority}
          onChange={(e) => { setPage(1); setFilters((f) => ({ ...f, priority: e.target.value })); }}
        >
          <option value="">Toutes priorites</option>
          {INCIDENT_PRIORITIES.map((p) => (
            <option key={p} value={p}>{formatLabel(p)}</option>
          ))}
        </select>
      </div>

      {loading && <Spinner />}
      {error && <ErrorMessage message={error} onRetry={load} />}
      {!loading && !error && (
        <>
          <DataTable columns={columns} rows={items} emptyMessage="Aucun incident." />
          <Pagination meta={meta} onPageChange={setPage} />
        </>
      )}

      <IncidentFormModal
        open={modalOpen}
        onClose={() => setModalOpen(false)}
        onSubmit={handleCreate}
        saving={saving}
      />

      <ConfirmDialog
        open={!!deleteTarget}
        title="Supprimer l'incident"
        message={`Supprimer "${deleteTarget?.title}" ?`}
        confirmLabel="Supprimer"
        danger
        onClose={() => setDeleteTarget(null)}
        onConfirm={handleDelete}
      />

      <AiAnalyzeModal
        open={ai.open}
        title={ai.title}
        loading={ai.loading}
        content={ai.content}
        error={ai.error}
        onClose={ai.close}
      />

      <Modal
        open={!!detailTarget}
        title={detailTarget?.title ?? 'Incident'}
        onClose={() => setDetailTarget(null)}
        wide
      >
        {detailTarget && (
          <div className="incident-detail">
            <div className="incident-detail__meta">
              <StatusBadge value={detailTarget.status} />
              <StatusBadge value={detailTarget.priority} />
            </div>
            <p>{detailTarget.description || 'Pas de description.'}</p>
            <dl className="detail-list">
              <dt>Equipement</dt>
              <dd>{detailTarget.device?.name ?? '—'}</dd>
              <dt>Assigne a</dt>
              <dd>{detailTarget.assignee?.name ?? '—'}</dd>
              <dt>Ouvert le</dt>
              <dd>{formatDate(detailTarget.opened_at)}</dd>
              {detailTarget.resolution_notes && (
                <>
                  <dt>Notes resolution</dt>
                  <dd>{detailTarget.resolution_notes}</dd>
                </>
              )}
            </dl>

            <div className="row-actions incident-detail__actions">
              {canUseAi && !['closed'].includes(detailTarget.status) && (
                <button
                  type="button"
                  className="btn btn--sm ai-btn-analyze"
                  disabled={actionLoading === detailTarget.id}
                  onClick={() => ai.runIncident(detailTarget)}
                >
                  Analyser (IA)
                </button>
              )}
              {canAssign && ['open'].includes(detailTarget.status) && (
                <button
                  type="button"
                  className="btn btn--secondary btn--sm"
                  disabled={actionLoading === detailTarget.id}
                  onClick={() => runTransition(detailTarget, 'assign', user.id)}
                >
                  M&apos;assigner
                </button>
              )}
              {canUpdate && ['assigned', 'open'].includes(detailTarget.status) && (
                <button
                  type="button"
                  className="btn btn--secondary btn--sm"
                  disabled={actionLoading === detailTarget.id}
                  onClick={() => runTransition(detailTarget, 'start')}
                >
                  Demarrer
                </button>
              )}
              {canUpdate && ['in_progress', 'assigned'].includes(detailTarget.status) && (
                <button
                  type="button"
                  className="btn btn--primary btn--sm"
                  disabled={actionLoading === detailTarget.id}
                  onClick={() => runTransition(detailTarget, 'resolve', 'Resolu via dashboard ORION')}
                >
                  Resoudre
                </button>
              )}
              {canClose && detailTarget.status === 'resolved' && (
                <button
                  type="button"
                  className="btn btn--secondary btn--sm"
                  disabled={actionLoading === detailTarget.id}
                  onClick={() => runTransition(detailTarget, 'close')}
                >
                  Clore
                </button>
              )}
            </div>

            {canAssign && users.length > 0 && detailTarget.status === 'open' && (
              <div className="assign-box">
                <label className="form-field">
                  <span>Assigner a un operateur</span>
                  <select
                    defaultValue=""
                    onChange={(e) => {
                      if (e.target.value) runTransition(detailTarget, 'assign', Number(e.target.value));
                    }}
                  >
                    <option value="">Choisir...</option>
                    {users.map((u) => (
                      <option key={u.id} value={u.id}>{u.name}</option>
                    ))}
                  </select>
                </label>
              </div>
            )}
          </div>
        )}
      </Modal>
    </div>
  );
}
