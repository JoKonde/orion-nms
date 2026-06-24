import { useCallback, useEffect, useState } from 'react';

const api = window.orionAgent;

export function App() {
  const [status, setStatus] = useState(null);
  const [form, setForm] = useState({
    apiBaseUrl: 'http://localhost:8001/api/v1',
    bootstrapKey: '',
    localIp: '',
    hostname: '',
  });
  const [busy, setBusy] = useState(false);
  const [message, setMessage] = useState('');

  const refresh = useCallback(async () => {
    const s = await api.getStatus();
    setStatus(s);
    setForm((f) => ({
      apiBaseUrl: s.config.apiBaseUrl || f.apiBaseUrl,
      bootstrapKey: f.bootstrapKey,
      localIp: s.config.localIp || f.localIp,
      hostname: s.config.hostname || f.hostname,
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
        localIp: form.localIp.trim(),
        hostname: form.hostname.trim(),
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
        localIp: form.localIp.trim(),
        hostname: form.hostname.trim(),
      });
      await api.register();
      api.start();
      setMessage('Agent enregistre. Synchronisation demarree.');
      await refresh();
    } catch (err) {
      setMessage(err.message || 'Echec enregistrement.');
    } finally {
      setBusy(false);
    }
  };

  const handleDetectIp = async () => {
    setBusy(true);
    setMessage('');
    try {
      const ip = await api.detectLocalIp();
      if (!ip) {
        setMessage('Detection automatique echouee. Saisissez votre IP LAN manuellement.');
        return;
      }
      setForm((f) => ({ ...f, localIp: ip }));
      await api.saveConfig({ localIp: ip });
      setMessage(`IP detectee : ${ip}`);
      await refresh();
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
  const runtimeLabel = status?.runtime === 'electron' ? 'Desktop (metriques systeme)' : 'Navigateur';

  return (
    <div className="agent-app">
      <header className="agent-header">
        <h1>ORION Agent</h1>
        <p className="agent-subtitle">Metriques CPU / RAM vers le serveur ORION</p>
      </header>

      <section className="agent-card">
        <h2>Statut</h2>
        <dl className="agent-dl">
          <dt>Mode</dt>
          <dd>{runtimeLabel}</dd>
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
          <dt>IP locale</dt>
          <dd className="mono">{status?.config?.localIp || '—'}</dd>
          <dt>UUID</dt>
          <dd className="mono">{status?.config?.agentUuid || '—'}</dd>
          <dt>Enregistre le</dt>
          <dd>
            {status?.config?.registeredAt
              ? new Date(status.config.registeredAt).toLocaleString('fr-FR')
              : '—'}
          </dd>
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
        <h2>Identite machine</h2>
        <p className="agent-hint">
          En mode navigateur, indiquez l&apos;IP LAN de ce PC (pas 127.0.0.1) pour apparaitre dans la topologie.
        </p>
        <label className="field">
          <span>Hostname (optionnel)</span>
          <input
            name="hostname"
            value={form.hostname}
            onChange={handleChange}
            placeholder="EBU-DEV1"
          />
        </label>
        <label className="field">
          <span>Adresse IP locale</span>
          <div className="field-row">
            <input
              name="localIp"
              value={form.localIp}
              onChange={handleChange}
              placeholder="192.168.1.50"
            />
            <button type="button" className="btn btn--ghost" onClick={handleDetectIp} disabled={busy}>
              Detecter
            </button>
          </div>
        </label>
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
        {status?.runtime === 'electron'
          ? "L'agent continue en arriere-plan, meme si vous fermez la fenetre."
          : "Heartbeat toutes les 60 s. Fermer l'onglet ou la fenetre arrete l'agent navigateur."}
      </footer>
    </div>
  );
}
