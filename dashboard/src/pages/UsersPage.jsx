import { useCallback, useEffect, useState } from 'react';
import * as usersApi from '../api/users';
import { useAuth } from '../auth/AuthContext';
import { UserFormModal } from '../components/users/UserFormModal';
import { PageHeader } from '../components/ui/PageHeader';
import { DataTable } from '../components/ui/DataTable';
import { Pagination } from '../components/ui/Pagination';
import { Spinner } from '../components/ui/Spinner';
import { ErrorMessage } from '../components/ui/ErrorMessage';
import { ConfirmDialog } from '../components/ui/ConfirmDialog';
import { usePermission } from '../hooks/usePermission';
import { formatDate, formatRole } from '../utils/format';

function ActiveBadge({ active }) {
  return (
    <span className={`status-badge status-badge--${active ? 'success' : 'danger'}`}>
      {active ? 'Actif' : 'Inactif'}
    </span>
  );
}

function RoleBadge({ role }) {
  const variant = role === 'admin' ? 'info' : role === 'operator' ? 'warning' : 'muted';

  return (
    <span className={`status-badge status-badge--${variant}`}>
      {formatRole(role)}
    </span>
  );
}

export function UsersPage() {
  const { user: currentUser } = useAuth();
  const canCreate = usePermission('users.create');
  const canUpdate = usePermission('users.update');
  const canDelete = usePermission('users.delete');

  const [items, setItems] = useState([]);
  const [meta, setMeta] = useState(null);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState(null);
  const [page, setPage] = useState(1);

  const [modalOpen, setModalOpen] = useState(false);
  const [editing, setEditing] = useState(null);
  const [saving, setSaving] = useState(false);
  const [deleteTarget, setDeleteTarget] = useState(null);

  const load = useCallback(async () => {
    setLoading(true);
    setError(null);
    try {
      const result = await usersApi.fetchUsers({ page, per_page: 15 });
      setItems(result.items);
      setMeta(result.meta);
    } catch (err) {
      setError(err.message);
    } finally {
      setLoading(false);
    }
  }, [page]);

  useEffect(() => {
    load();
  }, [load]);

  const openCreate = () => {
    setEditing(null);
    setModalOpen(true);
  };

  const openEdit = (row) => {
    setEditing(row);
    setModalOpen(true);
  };

  const handleSave = async (payload) => {
    setSaving(true);
    try {
      if (editing) {
        await usersApi.updateUser(editing.id, payload);
      } else {
        await usersApi.createUser(payload);
      }
      await load();
    } finally {
      setSaving(false);
    }
  };

  const handleDelete = async () => {
    if (!deleteTarget) return;
    await usersApi.deleteUser(deleteTarget.id);
    setDeleteTarget(null);
    await load();
  };

  const columns = [
    { key: 'name', label: 'Nom' },
    { key: 'email', label: 'Email' },
    {
      key: 'roles',
      label: 'Role',
      render: (r) => (
        <div className="role-badges">
          {(r.roles ?? []).map((role) => (
            <RoleBadge key={role} role={role} />
          ))}
          {(!r.roles || r.roles.length === 0) && <span>—</span>}
        </div>
      ),
    },
    {
      key: 'is_active',
      label: 'Statut',
      render: (r) => <ActiveBadge active={r.is_active !== false} />,
    },
    { key: 'created_at', label: 'Cree le', render: (r) => formatDate(r.created_at) },
    {
      key: 'actions',
      label: 'Actions',
      render: (r) => {
        const isSelf = currentUser?.id === r.id;

        return (
          <div className="row-actions">
            {canUpdate && (
              <button type="button" className="btn btn--secondary btn--sm" onClick={() => openEdit(r)}>
                Modifier
              </button>
            )}
            {canDelete && !isSelf && (
              <button type="button" className="btn btn--danger btn--sm" onClick={() => setDeleteTarget(r)}>
                Suppr.
              </button>
            )}
            {isSelf && <span className="row-actions__hint">Vous</span>}
          </div>
        );
      },
    },
  ];

  return (
    <div className="page crud-page">
      <PageHeader
        title="Utilisateurs"
        subtitle="Comptes admin et roles ORION (admin, operateur, lecteur)"
        actions={
          canCreate && (
            <button type="button" className="btn btn--primary" onClick={openCreate}>
              + Ajouter
            </button>
          )
        }
      />

      {loading && <Spinner label="Chargement des utilisateurs..." />}
      {error && <ErrorMessage message={error} onRetry={load} />}
      {!loading && !error && (
        <>
          <DataTable columns={columns} rows={items} emptyMessage="Aucun utilisateur." />
          <Pagination meta={meta} onPageChange={setPage} />
        </>
      )}

      <UserFormModal
        open={modalOpen}
        user={editing}
        onClose={() => setModalOpen(false)}
        onSubmit={handleSave}
        saving={saving}
      />

      <ConfirmDialog
        open={!!deleteTarget}
        title="Supprimer l'utilisateur"
        message={`Supprimer "${deleteTarget?.name}" ? Cette action est irreversible.`}
        confirmLabel="Supprimer"
        danger
        onClose={() => setDeleteTarget(null)}
        onConfirm={handleDelete}
      />
    </div>
  );
}
