import { useCallback, useEffect, useState } from 'react';
import * as devicesApi from '../api/devices';
import { PageHeader } from '../components/ui/PageHeader';
import { DataTable } from '../components/ui/DataTable';
import { Pagination } from '../components/ui/Pagination';
import { StatusBadge } from '../components/ui/StatusBadge';
import { Spinner } from '../components/ui/Spinner';
import { ErrorMessage } from '../components/ui/ErrorMessage';
import { ConfirmDialog } from '../components/ui/ConfirmDialog';
import { DeviceFormModal } from '../components/devices/DeviceFormModal';
import { usePermission } from '../hooks/usePermission';
import { DEVICE_TYPES, DEVICE_STATUSES } from '../utils/constants';
import { formatDate, formatLabel } from '../utils/format';

export function DevicesPage() {
  const canCreate = usePermission('devices.create');
  const canUpdate = usePermission('devices.update');
  const canDelete = usePermission('devices.delete');

  const [items, setItems] = useState([]);
  const [meta, setMeta] = useState(null);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState(null);
  const [page, setPage] = useState(1);
  const [filters, setFilters] = useState({ search: '', type: '', status: '' });

  const [modalOpen, setModalOpen] = useState(false);
  const [editing, setEditing] = useState(null);
  const [saving, setSaving] = useState(false);
  const [deleteTarget, setDeleteTarget] = useState(null);

  const load = useCallback(async () => {
    setLoading(true);
    setError(null);
    try {
      const params = { page, per_page: 15 };
      if (filters.search) params.search = filters.search;
      if (filters.type) params.type = filters.type;
      if (filters.status) params.status = filters.status;
      const result = await devicesApi.fetchDevices(params);
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

  const openCreate = () => {
    setEditing(null);
    setModalOpen(true);
  };

  const openEdit = (device) => {
    setEditing(device);
    setModalOpen(true);
  };

  const handleSave = async (form) => {
    setSaving(true);
    try {
      const payload = {
        name: form.name,
        ip_address: form.ip_address,
        mac_address: form.mac_address || null,
        type: form.type,
        vendor: form.vendor || null,
        model: form.model || null,
        status: form.status,
        description: form.description || null,
      };
      if (editing) {
        await devicesApi.updateDevice(editing.id, payload);
      } else {
        await devicesApi.createDevice(payload);
      }
      await load();
    } finally {
      setSaving(false);
    }
  };

  const handleDelete = async () => {
    if (!deleteTarget) return;
    await devicesApi.deleteDevice(deleteTarget.id);
    setDeleteTarget(null);
    await load();
  };

  const columns = [
    { key: 'name', label: 'Nom' },
    { key: 'ip_address', label: 'IP' },
    { key: 'type', label: 'Type', render: (r) => formatLabel(r.type) },
    { key: 'status', label: 'Statut', render: (r) => <StatusBadge value={r.status} /> },
    { key: 'vendor', label: 'Constructeur', render: (r) => r.vendor || '—' },
    { key: 'last_seen_at', label: 'Vu le', render: (r) => formatDate(r.last_seen_at) },
    {
      key: 'actions',
      label: 'Actions',
      render: (r) => (
        <div className="row-actions">
          {canUpdate && (
            <button type="button" className="btn btn--secondary btn--sm" onClick={() => openEdit(r)}>
              Modifier
            </button>
          )}
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
      <PageHeader
        title="Equipements"
        subtitle="Referentiel des devices supervises"
        actions={
          canCreate && (
            <button type="button" className="btn btn--primary" onClick={openCreate}>
              + Ajouter
            </button>
          )
        }
      />

      <div className="filters-bar">
        <input
          type="search"
          placeholder="Rechercher..."
          value={filters.search}
          onChange={(e) => {
            setPage(1);
            setFilters((f) => ({ ...f, search: e.target.value }));
          }}
        />
        <select
          value={filters.type}
          onChange={(e) => {
            setPage(1);
            setFilters((f) => ({ ...f, type: e.target.value }));
          }}
        >
          <option value="">Tous types</option>
          {DEVICE_TYPES.map((t) => (
            <option key={t} value={t}>{formatLabel(t)}</option>
          ))}
        </select>
        <select
          value={filters.status}
          onChange={(e) => {
            setPage(1);
            setFilters((f) => ({ ...f, status: e.target.value }));
          }}
        >
          <option value="">Tous statuts</option>
          {DEVICE_STATUSES.map((s) => (
            <option key={s} value={s}>{formatLabel(s)}</option>
          ))}
        </select>
      </div>

      {loading && <Spinner label="Chargement des equipements..." />}
      {error && <ErrorMessage message={error} onRetry={load} />}
      {!loading && !error && (
        <>
          <DataTable columns={columns} rows={items} emptyMessage="Aucun equipement." />
          <Pagination meta={meta} onPageChange={setPage} />
        </>
      )}

      <DeviceFormModal
        open={modalOpen}
        device={editing}
        onClose={() => setModalOpen(false)}
        onSubmit={handleSave}
        saving={saving}
      />

      <ConfirmDialog
        open={!!deleteTarget}
        title="Supprimer l'equipement"
        message={`Supprimer "${deleteTarget?.name}" ? Cette action est irreversible.`}
        confirmLabel="Supprimer"
        danger
        onClose={() => setDeleteTarget(null)}
        onConfirm={handleDelete}
      />
    </div>
  );
}
