import { useEffect, useState } from 'react';
import { Modal } from '../ui/Modal';
import {
  ALERT_OPERATORS,
  ALERT_RULE_TYPES,
  ALERT_SEVERITIES,
  METRIC_TYPES,
} from '../../utils/constants';
import { formatLabel } from '../../utils/format';

const EMPTY = {
  name: '',
  description: '',
  rule_type: 'device_offline',
  metric_type: 'cpu',
  operator: 'gt',
  threshold: 90,
  severity: 'critical',
  device_id: '',
  is_enabled: true,
  cooldown_minutes: 15,
};

/** Formulaire creation / edition regle d'alerte. */
export function AlertRuleFormModal({ open, rule, devices, onClose, onSubmit, saving }) {
  const [form, setForm] = useState(EMPTY);
  const [error, setError] = useState('');

  const isMetricRule = form.rule_type === 'metric_threshold';

  useEffect(() => {
    if (open) {
      setForm(
        rule
          ? {
              ...EMPTY,
              ...rule,
              device_id: rule.device_id ? String(rule.device_id) : '',
            }
          : { ...EMPTY },
      );
      setError('');
    }
  }, [open, rule]);

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

    const payload = {
      name: form.name,
      description: form.description || null,
      rule_type: form.rule_type,
      severity: form.severity,
      device_id: form.device_id ? Number(form.device_id) : null,
      is_enabled: form.is_enabled,
      cooldown_minutes: Number(form.cooldown_minutes) || 15,
    };

    if (isMetricRule) {
      payload.metric_type = form.metric_type;
      payload.operator = form.operator;
      payload.threshold = Number(form.threshold);
    } else {
      payload.metric_type = null;
      payload.operator = null;
      payload.threshold = null;
    }

    try {
      await onSubmit(payload);
      onClose();
    } catch (err) {
      setError(err.message || 'Erreur lors de la sauvegarde.');
    }
  };

  return (
    <Modal
      open={open}
      title={rule ? 'Modifier la regle' : 'Nouvelle regle d\'alerte'}
      onClose={onClose}
      wide
    >
      <form className="crud-form" onSubmit={handleSubmit}>
        {error && <div className="login-form__error" role="alert">{error}</div>}

        <div className="crud-form__grid">
          <label className="form-field">
            <span>Nom *</span>
            <input name="name" value={form.name} onChange={handleChange} required />
          </label>
          <label className="form-field">
            <span>Type de regle *</span>
            <select name="rule_type" value={form.rule_type} onChange={handleChange} required>
              {ALERT_RULE_TYPES.map((t) => (
                <option key={t} value={t}>{formatLabel(t)}</option>
              ))}
            </select>
          </label>
          <label className="form-field">
            <span>Severite *</span>
            <select name="severity" value={form.severity} onChange={handleChange} required>
              {ALERT_SEVERITIES.map((s) => (
                <option key={s} value={s}>{formatLabel(s)}</option>
              ))}
            </select>
          </label>
          <label className="form-field">
            <span>Equipement cible</span>
            <select name="device_id" value={form.device_id} onChange={handleChange}>
              <option value="">Tous les equipements</option>
              {devices.map((d) => (
                <option key={d.id} value={d.id}>{d.name} ({d.ip_address})</option>
              ))}
            </select>
          </label>
        </div>

        {isMetricRule && (
          <div className="crud-form__grid">
            <label className="form-field">
              <span>Metrique *</span>
              <select name="metric_type" value={form.metric_type} onChange={handleChange} required>
                {METRIC_TYPES.map((m) => (
                  <option key={m} value={m}>{formatLabel(m)}</option>
                ))}
              </select>
            </label>
            <label className="form-field">
              <span>Operateur *</span>
              <select name="operator" value={form.operator} onChange={handleChange} required>
                {ALERT_OPERATORS.map((o) => (
                  <option key={o} value={o}>{formatLabel(o)}</option>
                ))}
              </select>
            </label>
            <label className="form-field">
              <span>Seuil *</span>
              <input
                name="threshold"
                type="number"
                step="any"
                value={form.threshold}
                onChange={handleChange}
                required
              />
            </label>
          </div>
        )}

        <label className="form-field">
          <span>Description</span>
          <textarea name="description" value={form.description || ''} onChange={handleChange} rows={2} />
        </label>

        <div className="crud-form__grid">
          <label className="form-field form-field--checkbox">
            <input
              name="is_enabled"
              type="checkbox"
              checked={form.is_enabled}
              onChange={handleChange}
            />
            <span>Regle active</span>
          </label>
          <label className="form-field">
            <span>Cooldown (minutes)</span>
            <input
              name="cooldown_minutes"
              type="number"
              min="1"
              max="1440"
              value={form.cooldown_minutes}
              onChange={handleChange}
            />
          </label>
        </div>

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
