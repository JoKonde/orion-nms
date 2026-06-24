import { useCallback, useEffect, useMemo, useState } from 'react';
import * as reportsApi from '../api/reports';
import { PageHeader } from '../components/ui/PageHeader';
import { Spinner } from '../components/ui/Spinner';
import { ErrorMessage } from '../components/ui/ErrorMessage';
import { usePermission } from '../hooks/usePermission';

function defaultFromDate() {
  const d = new Date();
  d.setDate(d.getDate() - 30);
  return d.toISOString().slice(0, 10);
}

function defaultToDate() {
  return new Date().toISOString().slice(0, 10);
}

function formatGeneratedAt(iso) {
  if (!iso) return '—';
  try {
    return new Date(iso).toLocaleString('fr-FR');
  } catch {
    return iso;
  }
}

export function ReportsPage() {
  const canView = usePermission('reports.view');
  const [types, setTypes] = useState([]);
  const [selectedType, setSelectedType] = useState('network_summary');
  const [from, setFrom] = useState(defaultFromDate);
  const [to, setTo] = useState(defaultToDate);
  const [report, setReport] = useState(null);
  const [loadingTypes, setLoadingTypes] = useState(true);
  const [loadingReport, setLoadingReport] = useState(false);
  const [exporting, setExporting] = useState(null);
  const [error, setError] = useState(null);

  const currentType = useMemo(
    () => types.find((t) => t.id === selectedType),
    [types, selectedType],
  );

  useEffect(() => {
    if (!canView) return;
    setLoadingTypes(true);
    reportsApi
      .fetchReportTypes()
      .then((items) => {
        setTypes(items);
        if (items[0]?.id) setSelectedType(items[0].id);
      })
      .catch((err) => setError(err.message || 'Impossible de charger les rapports.'))
      .finally(() => setLoadingTypes(false));
  }, [canView]);

  const loadPreview = useCallback(async () => {
    setLoadingReport(true);
    setError(null);
    try {
      const params = { type: selectedType };
      if (currentType?.supports_period) {
        params.from = from;
        params.to = to;
      }
      const data = await reportsApi.fetchReportPreview(params);
      setReport(data);
    } catch (err) {
      setError(err.message || 'Apercu impossible.');
      setReport(null);
    } finally {
      setLoadingReport(false);
    }
  }, [selectedType, currentType, from, to]);

  const handleExport = async (format) => {
    setExporting(format);
    setError(null);
    try {
      const params = { type: selectedType, format };
      if (currentType?.supports_period) {
        params.from = from;
        params.to = to;
      }
      await reportsApi.downloadReport(params);
    } catch (err) {
      setError(err.message || 'Export impossible.');
    } finally {
      setExporting(null);
    }
  };

  if (!canView) {
    return (
      <div className="page">
        <PageHeader title="Rapports" subtitle="Permission requise (reports.view)" />
        <p>Contactez un administrateur pour acceder aux rapports ORION.</p>
      </div>
    );
  }

  if (loadingTypes) {
    return (
      <div className="page reports-page">
        <Spinner label="Chargement des rapports..." />
      </div>
    );
  }

  return (
    <div className="page reports-page">
      <PageHeader
        title="Rapports"
        subtitle="Exports CSV et rapports HTML imprimables (PDF via le navigateur)"
      />

      {error && <ErrorMessage message={error} onRetry={loadPreview} />}

      <div className="reports-layout">
        <aside className="card reports-sidebar">
          <h2 className="card__title">Type de rapport</h2>
          <ul className="reports-type-list">
            {types.map((type) => (
              <li key={type.id}>
                <button
                  type="button"
                  className={`reports-type-list__btn${
                    selectedType === type.id ? ' reports-type-list__btn--active' : ''
                  }`}
                  onClick={() => {
                    setSelectedType(type.id);
                    setReport(null);
                  }}
                >
                  <strong>{type.label}</strong>
                  <span>{type.description}</span>
                </button>
              </li>
            ))}
          </ul>
        </aside>

        <section className="card reports-main">
          <h2 className="card__title">{currentType?.label ?? 'Rapport'}</h2>
          <p className="reports-main__hint">{currentType?.description}</p>

          {currentType?.supports_period && (
            <div className="reports-filters">
              <label>
                Du
                <input type="date" value={from} onChange={(e) => setFrom(e.target.value)} />
              </label>
              <label>
                Au
                <input type="date" value={to} onChange={(e) => setTo(e.target.value)} />
              </label>
            </div>
          )}

          <div className="reports-actions">
            <button
              type="button"
              className="btn btn--primary"
              onClick={loadPreview}
              disabled={loadingReport}
            >
              {loadingReport ? 'Chargement…' : 'Apercu'}
            </button>
            <button
              type="button"
              className="btn btn--secondary"
              onClick={() => handleExport('csv')}
              disabled={!!exporting}
            >
              {exporting === 'csv' ? 'Export…' : 'Exporter CSV'}
            </button>
            <button
              type="button"
              className="btn btn--secondary"
              onClick={() => handleExport('html')}
              disabled={!!exporting}
            >
              {exporting === 'html' ? 'Ouverture…' : 'Ouvrir HTML / PDF'}
            </button>
          </div>

          {loadingReport && <Spinner label="Generation du rapport..." />}

          {!loadingReport && report && (
            <div className="reports-preview">
              <p className="reports-preview__meta">
                Genere le {formatGeneratedAt(report.generated_at)}
                {report.period && (
                  <>
                    {' '}
                    — du {new Date(report.period.from).toLocaleDateString('fr-FR')} au{' '}
                    {new Date(report.period.to).toLocaleDateString('fr-FR')}
                  </>
                )}
              </p>

              {report.summary?.length > 0 && (
                <dl className="reports-summary">
                  {report.summary.map((row) => (
                    <div key={row.label} className="reports-summary__item">
                      <dt>{row.label}</dt>
                      <dd>{row.value}</dd>
                    </div>
                  ))}
                </dl>
              )}

              {report.columns?.length > 0 && (
                <div className="reports-table-wrap">
                  <table className="data-table reports-table">
                    <thead>
                      <tr>
                        {report.columns.map((col) => (
                          <th key={col.key}>{col.label}</th>
                        ))}
                      </tr>
                    </thead>
                    <tbody>
                      {report.rows?.length ? (
                        report.rows.map((row, index) => (
                          <tr key={index}>
                            {report.columns.map((col) => (
                              <td key={col.key}>{row[col.key] ?? '—'}</td>
                            ))}
                          </tr>
                        ))
                      ) : (
                        <tr>
                          <td colSpan={report.columns.length} className="reports-table__empty">
                            Aucune donnee pour cette periode.
                          </td>
                        </tr>
                      )}
                    </tbody>
                  </table>
                </div>
              )}
            </div>
          )}
        </section>
      </div>
    </div>
  );
}
