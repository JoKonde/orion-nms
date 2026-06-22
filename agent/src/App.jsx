import { useCallback, useEffect, useState } from 'react';

const api = window.orionAgent;

export function App() {
  const [status, setStatus] = useState(null);
  const [form, setForm] = useState({
    apiBaseUrl: 'http://localhost:8001/api/v1',
    bootstrapKey: '',
  });
  const [busy, setBusy] = useState(false);
  const [message, setMessage] = useState('');

  const refresh = useCallback(async () => {
    const s = await api.getStatus();
    setStatus(s);
    setForm((f) => ({
      apiBaseUrl: s.config.apiBaseUrl || f.apiBaseUrl,
      bootstrapKey: f.bootstrapKey,
    }));
  }, []);

  useEffect(() => {
    refresh();
    return api.onStatus(setStatus);
  }, [refresh]);

  const handleChange = (e) => {
    const { name, value } = e.target;
    setForm((prev) => ({ ...prev, [name]: value }));
  };

  const handleSave = async () => {
    setBusy(true);
    setMessage('');
    try {
      await api.saveConfig({
        apiBaseUrl: form.apiBaseUrl.trim(),
        bootstrapKey: form.bootstrapKey.trim(),
      });
      setMessage('Configuration enregistree.');
      await refresh();
    } catch (err) {
      setMessage(err.message || 'Erreur.');
    } finally {
      setBusy(false);
    }
  };

  const handleRegister = async () => {
    setBusy(true);
    setMessage('');
    try {
      await api.saveConfig({
        apiBaseUrl: form.apiBaseUrl.trim(),
        bootstrapKey: form.bootstrapKey.trim(),
      });
      await api.register();
      setMessage('Agent enregistre. Synchronisation demarree.');
      await refresh();
    } catch (err) {
      setMessage(err.message || 'Echec enregistrement.');
    } finally {
      setBusy(false);
    }
  };

  const toggleRun = async () => {
    setBusy(true);
    try {
      if (status?.running) await api.stop();
      else await api.start();
      await refresh();
    } finally {
      setBusy(false);
    }
  };

  const registered = status?.registered;

  return (
    <div className="agent-app">
      <header className="agent-header">
        <h1>ORION Agent</h1>
        <p className="agent-subtitle">Metriques CPU / RAM vers le serveur ORION</p>
      </header>

      <section className="agent-card">
        <h2>Statut</h2>
        <dl className="agent-dl">
          <dt>Agent</dt>
          <dd>
            <span className={`pill pill--${status?.running ? 'ok' : 'off'}`}>
              {status?.running ? 'Actif' : 'Arrete'}
            </span>
          </dd>
          <dt>Enregistrement</dt>
          <dd>{registered ? 'OK' : 'Non enregistre'}</dd>
          <dt>Hostname</dt>
          <dd>{status?.config?.hostname || '—'}</dd>
          <dt>UUID</dt>
          <dd className="mono">{status?.config?.agentUuid || '—'}</dd>
          <dt>Derniere sync</dt>
          <dd>{status?.lastSyncAt ? new Date(status.lastSyncAt).toLocaleString('fr-FR') : '—'}</dd>
        </dl>
        {status?.lastError && (
          <p className="agent-error" role="alert">{status.lastError}</p>
        )}
        {registered && (
          <button type="button" className="btn" onClick={toggleRun} disabled={busy}>
            {status?.running ? 'Arreter' : 'Demarrer'}
          </button>
        )}
      </section>

      <section className="agent-card">
        <h2>Configuration serveur</h2>
        <label className="field">
          <span>URL API ORION</span>
          <input
            name="apiBaseUrl"
            value={form.apiBaseUrl}
            onChange={handleChange}
            placeholder="http://localhost:8001/api/v1"
          />
        </label>
        <label className="field">
          <span>Cle bootstrap (.env ORION_AGENT_BOOTSTRAP_KEY)</span>
          <input
            name="bootstrapKey"
            type="password"
            value={form.bootstrapKey}
            onChange={handleChange}
            placeholder="orion-bootstrap-change-me"
          />
        </label>
        <div className="agent-actions">
          <button type="button" className="btn btn--ghost" onClick={handleSave} disabled={busy}>
            Enregistrer
          </button>
          <button type="button" className="btn btn--primary" onClick={handleRegister} disabled={busy}>
            {registered ? 'Re-enregistrer' : 'Enregistrer sur ORION'}
          </button>
        </div>
        {message && <p className="agent-msg">{message}</p>}
      </section>

      <footer className="agent-footer">
        Fermer la fenetre minimise dans la barre des taches. Heartbeat : 60 s.
      </footer>
    </div>
  );
}
