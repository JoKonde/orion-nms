import { useEffect, useMemo, useState } from 'react';
import { Modal } from '../ui/Modal';
import { USER_ROLES } from '../../utils/constants';

const EMPTY = {
  name: '',
  email: '',
  password: '',
  password_confirmation: '',
  role: 'operator',
  is_active: true,
};

/** Formulaire creation / edition utilisateur admin. */
export function UserFormModal({ open, user, onClose, onSubmit, saving }) {
  const [form, setForm] = useState(EMPTY);
  const [error, setError] = useState('');

  const isEdit = Boolean(user);

  useEffect(() => {
    if (open) {
      setForm(
        user
          ? {
              name: user.name ?? '',
              email: user.email ?? '',
              password: '',
              password_confirmation: '',
              role: user.roles?.[0] ?? 'operator',
              is_active: user.is_active !== false,
            }
          : { ...EMPTY },
      );
      setError('');
    }
  }, [open, user]);

  const roleHelp = useMemo(
    () => USER_ROLES.find((r) => r.value === form.role)?.description ?? '',
    [form.role],
  );

  const handleChange = (e) => {
    const { name, value, type, checked } = e.target;
    setForm((prev) => ({
      ...prev,
      [name]: type === 'checkbox' ? checked : value,
    }));
  };

  const handleSubmit = async (e) => {
    e.preventDefault();
    setError('');

    if (!isEdit && form.password.length < 8) {
      setError('Le mot de passe doit contenir au moins 8 caracteres.');
      return;
    }

    if (!isEdit && form.password !== form.password_confirmation) {
      setError('Les mots de passe ne correspondent pas.');
      return;
    }

    if (isEdit && form.password && form.password !== form.password_confirmation) {
      setError('Les mots de passe ne correspondent pas.');
      return;
    }

    try {
      const payload = {
        name: form.name.trim(),
        email: form.email.trim(),
        roles: [form.role],
        is_active: form.is_active,
      };

      if (!isEdit || form.password) {
        payload.password = form.password;
        payload.password_confirmation = form.password_confirmation;
      }

      await onSubmit(payload);
      onClose();
    } catch (err) {
      setError(err.message || 'Erreur lors de la sauvegarde.');
    }
  };

  return (
    <Modal
      open={open}
      title={isEdit ? 'Modifier utilisateur' : 'Nouvel utilisateur'}
      onClose={onClose}
      wide
    >
      <form className="crud-form" onSubmit={handleSubmit}>
        {error && <div className="login-form__error" role="alert">{error}</div>}

        <div className="crud-form__grid">
          <label className="form-field">
            <span>Nom</span>
            <input name="name" value={form.name} onChange={handleChange} required />
          </label>
          <label className="form-field">
            <span>Email</span>
            <input
              name="email"
              type="email"
              value={form.email}
              onChange={handleChange}
              required
              autoComplete="off"
            />
          </label>
          <label className="form-field">
            <span>Mot de passe {isEdit && '(laisser vide pour ne pas changer)'}</span>
            <input
              name="password"
              type="password"
              value={form.password}
              onChange={handleChange}
              required={!isEdit}
              autoComplete="new-password"
            />
          </label>
          <label className="form-field">
            <span>Confirmation mot de passe</span>
            <input
              name="password_confirmation"
              type="password"
              value={form.password_confirmation}
              onChange={handleChange}
              required={!isEdit && Boolean(form.password)}
              autoComplete="new-password"
            />
          </label>
          <label className="form-field">
            <span>Role</span>
            <select name="role" value={form.role} onChange={handleChange} required>
              {USER_ROLES.map((role) => (
                <option key={role.value} value={role.value}>
                  {role.label}
                </option>
              ))}
            </select>
          </label>
          <label className="form-field form-field--checkbox">
            <input
              name="is_active"
              type="checkbox"
              checked={form.is_active}
              onChange={handleChange}
            />
            <span>Compte actif</span>
          </label>
        </div>

        {roleHelp && <p className="form-hint">{roleHelp}</p>}

        <div className="crud-form__actions">
          <button type="button" className="btn btn--secondary" onClick={onClose}>
            Annuler
          </button>
          <button type="submit" className="btn btn--primary" disabled={saving}>
            {saving ? 'Enregistrement...' : isEdit ? 'Enregistrer' : 'Creer'}
          </button>
        </div>
      </form>
    </Modal>
  );
}
