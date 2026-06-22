import { useEffect, useState } from 'react';
import { Modal } from '../ui/Modal';
import { DEVICE_TYPES, DEVICE_STATUSES } from '../../utils/constants';
import { formatLabel } from '../../utils/format';

const EMPTY = {
  name: '',
  ip_address: '',
  mac_address: '',
  type: 'pc',
  vendor: '',
  model: '',
  status: 'unknown',
  description: '',
};

/** Formulaire creation / edition device. */
export function DeviceFormModal({ open, device, onClose, onSubmit, saving }) {
  const [form, setForm] = useState(EMPTY);
  const [error, setError] = useState('');

  useEffect(() => {
    if (open) {
      setForm(device ? { ...EMPTY, ...device } : { ...EMPTY });
      setError('');
    }
  }, [open, device]);

  const handleChange = (e) => {
    const { name, value } = e.target;
    setForm((prev) => ({ ...prev, [name]: value }));
  };

  const handleSubmit = async (e) => {
    e.preventDefault();
    setError('');
    try {
      await onSubmit(form);
      onClose();
    } catch (err) {
      setError(err.message || 'Erreur lors de la sauvegarde.');
    }
  };

  return (
    <Modal open={open} title={device ? 'Modifier equipement' : 'Nouvel equipement'} onClose={onClose} wide>
      <form className="crud-form" onSubmit={handleSubmit}>
        {error && <div className="login-form__error" role="alert">{error}</div>}

        <div className="crud-form__grid">
          <label className="form-field">
            <span>Nom *</span>
            <input name="name" value={form.name} onChange={handleChange} required />
          </label>
          <label className="form-field">
            <span>Adresse IP *</span>
            <input name="ip_address" value={form.ip_address} onChange={handleChange} required />
          </label>
          <label className="form-field">
            <span>MAC</span>
            <input name="mac_address" value={form.mac_address || ''} onChange={handleChange} placeholder="AA:BB:CC:DD:EE:FF" />
          </label>
          <label className="form-field">
            <span>Type *</span>
            <select name="type" value={form.type} onChange={handleChange} required>
              {DEVICE_TYPES.map((t) => (
                <option key={t} value={t}>{formatLabel(t)}</option>
              ))}
            </select>
          </label>
          <label className="form-field">
            <span>Statut</span>
            <select name="status" value={form.status} onChange={handleChange}>
              {DEVICE_STATUSES.map((s) => (
                <option key={s} value={s}>{formatLabel(s)}</option>
              ))}
            </select>
          </label>
          <label className="form-field">
            <span>Constructeur</span>
            <input name="vendor" value={form.vendor || ''} onChange={handleChange} />
          </label>
          <label className="form-field">
            <span>Modele</span>
            <input name="model" value={form.model || ''} onChange={handleChange} />
          </label>
        </div>
        <label className="form-field">
          <span>Description</span>
          <textarea name="description" value={form.description || ''} onChange={handleChange} rows={3} />
        </label>

        <div className="crud-form__actions">
          <button type="button" className="btn btn--secondary" onClick={onClose}>Annuler</button>
          <button type="submit" className="btn btn--primary" disabled={saving}>
            {saving ? 'Enregistrement...' : 'Enregistrer'}
          </button>
        </div>
      </form>
    </Modal>
  );
}
