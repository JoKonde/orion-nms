import { useCallback, useEffect, useState } from 'react';
import { useNavigate } from 'react-router-dom';
import * as alertsApi from '../api/alerts';
import * as alertRulesApi from '../api/alertRules';
import * as devicesApi from '../api/devices';
import { AlertRuleFormModal } from '../components/alerts/AlertRuleFormModal';
import { PageHeader } from '../components/ui/PageHeader';
import { DataTable } from '../components/ui/DataTable';
import { Pagination } from '../components/ui/Pagination';
import { StatusBadge } from '../components/ui/StatusBadge';
import { Spinner } from '../components/ui/Spinner';
import { ErrorMessage } from '../components/ui/ErrorMessage';
import { ConfirmDialog } from '../components/ui/ConfirmDialog';
import { AiAnalyzeModal, useAiAnalyze } from '../components/ai/AiPanel';
import { usePermission } from '../hooks/usePermission';
import { useRealtimeRefresh } from '../hooks/useRealtimeRefresh';
import { ALERT_STATUSES, ALERT_SEVERITIES } from '../utils/constants';
import { formatDate, formatLabel } from '../utils/format';

export function AlertsPage() {
  const canManage = usePermission('alerts.manage');
  const canCreateIncident = usePermission('incidents.create');
  const canUseAi = usePermission('ai.use');
  const ai = useAiAnalyze();
  const navigate = useNavigate();

  const [tab, setTab] = useState('events');

  const [items, setItems] = useState([]);
  const [meta, setMeta] = useState(null);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState(null);
  const [page, setPage] = useState(1);
  const [filters, setFilters] = useState({ status: '', severity: '' });
  const [actionLoading, setActionLoading] = useState(null);

  const [rules, setRules] = useState([]);
  const [rulesMeta, setRulesMeta] = useState(null);
  const [rulesLoading, setRulesLoading] = useState(false);
  const [rulesError, setRulesError] = useState(null);
  const [rulesPage, setRulesPage] = useState(1);
  const [devices, setDevices] = useState([]);
  const [ruleModalOpen, setRuleModalOpen] = useState(false);
  const [editingRule, setEditingRule] = useState(null);
  const [ruleSaving, setRuleSaving] = useState(false);
  const [deleteRuleTarget, setDeleteRuleTarget] = useState(null);

  const load = useCallback(async () => {
    setLoading(true);
    setError(null);
    try {
      const params = { page, per_page: 15 };
      if (filters.status) params.status = filters.status;
      if (filters.severity) params.severity = filters.severity;
      const result = await alertsApi.fetchAlerts(params);
      setItems(result.items);
      setMeta(result.meta);
    } catch (err) {
      setError(err.message);
    } finally {
      setLoading(false);
    }
  }, [page, filters]);

  const loadRules = useCallback(async () => {
    setRulesLoading(true);
    setRulesError(null);
    try {
      const result = await alertRulesApi.fetchAlertRules({ page: rulesPage, per_page: 15 });
      setRules(result.items);
      setRulesMeta(result.meta);
    } catch (err) {
      setRulesError(err.message);
    } finally {
      setRulesLoading(false);
    }
  }, [rulesPage]);

  const loadDevices = useCallback(async () => {
    try {
      const result = await devicesApi.fetchDevices({ per_page: 100 });
      setDevices(result.items);
    } catch {
      setDevices([]);
    }
  }, []);

  useEffect(() => {
    if (tab === 'events') {
      load();
    }
  }, [tab, load]);

  useEffect(() => {
    if (tab === 'rules') {
      loadRules();
    }
  }, [tab, loadRules]);

  useEffect(() => {
    if (canManage) {
      loadDevices();
    }
  }, [canManage, loadDevices]);

  useRealtimeRefresh('alert.raised', load);

  const runAction = async (id, action) => {
    setActionLoading(id);
    try {
      if (action === 'ack') await alertsApi.acknowledgeAlert(id);
      if (action === 'resolve') await alertsApi.resolveAlert(id);
      if (action === 'escalate') {
        const incident = await alertsApi.escalateAlert(id);
        navigate(`/incidents?highlight=${incident.id}`);
        return;
      }
      await load();
    } catch (err) {
      setError(err.message);
    } finally {
      setActionLoading(null);
    }
  };

  const openCreateRule = () => {
    setEditingRule(null);
    setRuleModalOpen(true);
  };

  const openEditRule = (rule) => {
    setEditingRule(rule);
    setRuleModalOpen(true);
  };

  const handleSaveRule = async (payload) => {
    setRuleSaving(true);
    try {
      if (editingRule) {
        await alertRulesApi.updateAlertRule(editingRule.id, payload);
      } else {
        await alertRulesApi.createAlertRule(payload);
      }
      await loadRules();
    } finally {
      setRuleSaving(false);
    }
  };

  const handleDeleteRule = async () => {
    if (!deleteRuleTarget) return;
    await alertRulesApi.deleteAlertRule(deleteRuleTarget.id);
    setDeleteRuleTarget(null);
    await loadRules();
  };

  const alertColumns = [
    { key: 'title', label: 'Titre' },
    { key: 'severity', label: 'Severite', render: (r) => <StatusBadge value={r.severity} /> },
    { key: 'status', label: 'Statut', render: (r) => <StatusBadge value={r.status} /> },
    { key: 'device', label: 'Equipement', render: (r) => r.device?.name ?? `#${r.device_id}` },
    { key: 'raised_at', label: 'Levee le', render: (r) => formatDate(r.raised_at) },
    {
      key: 'actions',
      label: 'Actions',
      render: (r) => (
        <div className="row-actions">
          {canUseAi && r.status !== 'resolved' && (
            <button
              type="button"
              className="btn btn--sm ai-btn-analyze"
              disabled={actionLoading === r.id}
              onClick={() => ai.runAlert(r)}
            >
              IA
            </button>
          )}
          {canManage && r.status === 'raised' && (
            <button
              type="button"
              className="btn btn--secondary btn--sm"
              disabled={actionLoading === r.id}
              onClick={() => runAction(r.id, 'ack')}
            >
              Acquitter
            </button>
          )}
          {canManage && r.status !== 'resolved' && (
            <button
              type="button"
              className="btn btn--secondary btn--sm"
              disabled={actionLoading === r.id}
              onClick={() => runAction(r.id, 'resolve')}
            >
              Resoudre
            </button>
          )}
          {canCreateIncident && r.status !== 'resolved' && (
            <button
              type="button"
              className="btn btn--primary btn--sm"
              disabled={actionLoading === r.id}
              onClick={() => runAction(r.id, 'escalate')}
            >
              Escalader
            </button>
          )}
        </div>
      ),
    },
  ];

  const ruleColumns = [
    { key: 'name', label: 'Nom' },
    { key: 'rule_type', label: 'Type', render: (r) => formatLabel(r.rule_type) },
    { key: 'severity', label: 'Severite', render: (r) => <StatusBadge value={r.severity} /> },
    {
      key: 'target',
      label: 'Cible',
      render: (r) => (r.device ? r.device.name : 'Tous'),
    },
    {
      key: 'is_enabled',
      label: 'Active',
      render: (r) => (r.is_enabled ? 'Oui' : 'Non'),
    },
    {
      key: 'actions',
      label: 'Actions',
      render: (r) => (
        <div className="row-actions">
          {canManage && (
            <>
              <button type="button" className="btn btn--secondary btn--sm" onClick={() => openEditRule(r)}>
                Modifier
              </button>
              <button type="button" className="btn btn--danger btn--sm" onClick={() => setDeleteRuleTarget(r)}>
                Suppr.
              </button>
            </>
          )}
        </div>
      ),
    },
  ];

  return (
    <div className="page crud-page">
      <PageHeader
        title="Alertes"
        subtitle="Supervision reseau — alertes declenchees et regles"
        actions={
          tab === 'rules' && canManage && (
            <button type="button" className="btn btn--primary" onClick={openCreateRule}>
              + Nouvelle regle
            </button>
          )
        }
      />

      <div className="page-tabs">
        <button
          type="button"
          className={`page-tabs__btn ${tab === 'events' ? 'page-tabs__btn--active' : ''}`}
          onClick={() => setTab('events')}
        >
          Alertes declenchees
        </button>
        <button
          type="button"
          className={`page-tabs__btn ${tab === 'rules' ? 'page-tabs__btn--active' : ''}`}
          onClick={() => setTab('rules')}
        >
          Regles d&apos;alerte
        </button>
      </div>

      {tab === 'events' && (
        <>
          <div className="filters-bar">
            <select
              value={filters.status}
              onChange={(e) => { setPage(1); setFilters((f) => ({ ...f, status: e.target.value })); }}
            >
              <option value="">Tous statuts</option>
              {ALERT_STATUSES.map((s) => (
                <option key={s} value={s}>{formatLabel(s)}</option>
              ))}
            </select>
            <select
              value={filters.severity}
              onChange={(e) => { setPage(1); setFilters((f) => ({ ...f, severity: e.target.value })); }}
            >
              <option value="">Toutes severites</option>
              {ALERT_SEVERITIES.map((s) => (
                <option key={s} value={s}>{formatLabel(s)}</option>
              ))}
            </select>
          </div>

          {loading && <Spinner />}
          {error && <ErrorMessage message={error} onRetry={load} />}
          {!loading && !error && (
            <>
              <DataTable columns={alertColumns} rows={items} emptyMessage="Aucune alerte." />
              <Pagination meta={meta} onPageChange={setPage} />
            </>
          )}
        </>
      )}

      {tab === 'rules' && (
        <>
          {rulesLoading && <Spinner />}
          {rulesError && <ErrorMessage message={rulesError} onRetry={loadRules} />}
          {!rulesLoading && !rulesError && (
            <>
              <DataTable
                columns={ruleColumns}
                rows={rules}
                emptyMessage="Aucune regle. Creez une regle pour declencher des alertes automatiques."
              />
              <Pagination meta={rulesMeta} onPageChange={setRulesPage} />
            </>
          )}
        </>
      )}

      <AiAnalyzeModal
        open={ai.open}
        title={ai.title}
        loading={ai.loading}
        content={ai.content}
        error={ai.error}
        onClose={ai.close}
      />

      <AlertRuleFormModal
        open={ruleModalOpen}
        rule={editingRule}
        devices={devices}
        onClose={() => setRuleModalOpen(false)}
        onSubmit={handleSaveRule}
        saving={ruleSaving}
      />

      <ConfirmDialog
        open={!!deleteRuleTarget}
        title="Supprimer la regle"
        message={`Supprimer "${deleteRuleTarget?.name}" ?`}
        confirmLabel="Supprimer"
        danger
        onClose={() => setDeleteRuleTarget(null)}
        onConfirm={handleDeleteRule}
      />
    </div>
  );
}
