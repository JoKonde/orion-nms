import { useEffect, useState } from 'react';
import { Modal } from '../ui/Modal';
import { INCIDENT_PRIORITIES } from '../../utils/constants';
import { formatLabel } from '../../utils/format';

const EMPTY = {
  title: '',
  description: '',
  priority: 'medium',
  device_id: '',
};

/** Formulaire creation incident. */
export function IncidentFormModal({ open, onClose, onSubmit, saving }) {
  const [form, setForm] = useState(EMPTY);
  const [error, setError] = useState('');

  useEffect(() => {
    if (open) {
      setForm({ ...EMPTY });
      setError('');
    }
  }, [open]);

  const handleChange = (e) => {
    const { name, value } = e.target;
    setForm((prev) => ({ ...prev, [name]: value }));
  };

  const handleSubmit = async (e) => {
    e.preventDefault();
    setError('');
    try {
      const payload = {
        title: form.title,
        description: form.description || undefined,
        priority: form.priority,
        device_id: form.device_id ? Number(form.device_id) : undefined,
      };
      await onSubmit(payload);
      onClose();
    } catch (err) {
      setError(err.message || 'Erreur lors de la creation.');
    }
  };

  return (
    <Modal open={open} title="Nouvel incident" onClose={onClose}>
      <form className="crud-form" onSubmit={handleSubmit}>
        {error && <div className="login-form__error" role="alert">{error}</div>}

        <label className="form-field">
          <span>Titre *</span>
          <input name="title" value={form.title} onChange={handleChange} required />
        </label>
        <label className="form-field">
          <span>Priorite *</span>
          <select name="priority" value={form.priority} onChange={handleChange}>
            {INCIDENT_PRIORITIES.map((p) => (
              <option key={p} value={p}>{formatLabel(p)}</option>
            ))}
          </select>
        </label>
        <label className="form-field">
          <span>ID equipement (optionnel)</span>
          <input name="device_id" type="number" value={form.device_id} onChange={handleChange} min="1" />
        </label>
        <label className="form-field">
          <span>Description</span>
          <textarea name="description" value={form.description} onChange={handleChange} rows={4} />
        </label>

        <div className="crud-form__actions">
          <button type="button" className="btn btn--secondary" onClick={onClose}>Annuler</button>
          <button type="submit" className="btn btn--primary" disabled={saving}>
            {saving ? 'Creation...' : 'Creer'}
          </button>
        </div>
      </form>
    </Modal>
  );
}
