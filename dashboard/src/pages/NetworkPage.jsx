import { useCallback, useEffect, useState } from 'react';
import * as networkApi from '../api/network';
import { PageHeader } from '../components/ui/PageHeader';
import { Spinner } from '../components/ui/Spinner';
import { ErrorMessage } from '../components/ui/ErrorMessage';

export function NetworkPage() {
  const [context, setContext] = useState(null);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState(null);
  const [copyMsg, setCopyMsg] = useState('');

  const load = useCallback(async () => {
    setLoading(true);
    setError(null);
    try {
      const data = await networkApi.fetchNetworkDetected();
      setContext(data);
    } catch (err) {
      setError(err.message);
    } finally {
      setLoading(false);
    }
  }, []);

  useEffect(() => {
    load();
  }, [load]);

  const handleCopy = async (command) => {
    try {
      await navigator.clipboard.writeText(command);
      setCopyMsg('Commande copiee.');
      setTimeout(() => setCopyMsg(''), 2500);
    } catch {
      setCopyMsg('Copie impossible — selectionnez la commande manuellement.');
    }
  };

  if (loading) {
    return (
      <div className="page">
        <Spinner label="Detection reseau..." />
      </div>
    );
  }

  if (error) {
    return (
      <div className="page">
        <ErrorMessage message={error} onRetry={load} />
      </div>
    );
  }

  const cliCommand = context?.cli_scan_command ?? 'php artisan orion:network-detect --scan';

  return (
    <div className="page crud-page">
      <PageHeader
        title="Reseau"
        subtitle="Detection locale et decouverte Nmap"
      />

      <div className="network-grid">
        <article className="card">
          <h3 className="card__title">Subnet effectif</h3>
          <p className="network-subnet">{context?.effective_subnet}</p>
          <p className="card__body">
            Source : <strong>{context?.source}</strong>
            <br />
            {context?.message}
          </p>
          {context?.configured_subnet && (
            <p className="card__body">
              Configure (.env) : <code>{context.configured_subnet}</code>
            </p>
          )}
        </article>

        <article className="card">
          <h3 className="card__title">Scan Nmap (terminal Laravel)</h3>
          <p className="card__body">
            Le scan reseau ne peut pas etre lance depuis le dashboard sous Windows.
            Ouvrez un terminal dans le dossier <code>core</code> et executez :
          </p>
          <div className="cli-command">
            <code className="cli-command__text">{cliCommand}</code>
            <button
              type="button"
              className="btn btn--secondary btn--sm"
              onClick={() => handleCopy(cliCommand)}
            >
              Copier
            </button>
          </div>
          {context?.cli_scan_command_subnet && (
            <p className="card__body">
              Avec subnet explicite :{' '}
              <code>{context.cli_scan_command_subnet}</code>
            </p>
          )}
          {copyMsg && <p className="network-msg">{copyMsg}</p>}
          <p className="card__body network-hint">
            Apres le scan, les equipements absents du resultat Nmap sont retires de la base
            (devices decouverts par Nmap uniquement).
          </p>
        </article>
      </div>

      {context?.detected_interfaces?.length > 0 && (
        <article className="card network-interfaces">
          <h3 className="card__title">Interfaces detectees</h3>
          <ul>
            {context.detected_interfaces.map((iface) => (
              <li key={`${iface.name}-${iface.ip}`}>
                <strong>{iface.name}</strong> — {iface.ip} / {iface.prefix ?? '?'}
                {iface.subnet && <span> → {iface.subnet}</span>}
              </li>
            ))}
          </ul>
        </article>
      )}

      {context?.subnet_help && (
        <article className="card">
          <h3 className="card__title">Aide saisie subnet</h3>
          <p className="card__body"><strong>Windows :</strong> {context.subnet_help.windows}</p>
          <p className="card__body"><strong>Linux :</strong> {context.subnet_help.linux}</p>
        </article>
      )}
    </div>
  );
}
