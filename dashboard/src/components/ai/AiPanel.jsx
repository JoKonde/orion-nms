import { useCallback, useEffect, useRef, useState } from 'react';
import * as aiApi from '../../api/ai';
import { Spinner } from '../ui/Spinner';
import { AiMarkdown } from './AiMarkdown';

export function AiAnalyzeModal({ open, title, loading, content, error, onClose }) {
  if (!open) return null;

  return (
    <div className="ai-analyze-overlay" onClick={onClose} role="presentation">
      <div className="ai-analyze-modal" onClick={(e) => e.stopPropagation()} role="dialog">
        <header className="ai-analyze-modal__header">
          <h2>{title}</h2>
          <button type="button" className="ai-analyze-modal__close" onClick={onClose} aria-label="Fermer">
            ×
          </button>
        </header>
        <div className="ai-analyze-modal__body">
          {loading && <Spinner label="Analyse ORION AI..." />}
          {error && <p className="ai-error">{error}</p>}
          {!loading && !error && content && (
            <div className="ai-analyze-content">
              <AiMarkdown>{content}</AiMarkdown>
            </div>
          )}
        </div>
      </div>
    </div>
  );
}

export function useAiAnalyze() {
  const [open, setOpen] = useState(false);
  const [title, setTitle] = useState('');
  const [loading, setLoading] = useState(false);
  const [content, setContent] = useState('');
  const [error, setError] = useState(null);

  const runAlert = useCallback(async (alert) => {
    setOpen(true);
    setTitle(`ORION AI — ${alert.title}`);
    setLoading(true);
    setContent('');
    setError(null);
    try {
      const insight = await aiApi.analyzeAlert(alert.id);
      setContent(insight.content);
    } catch (err) {
      setError(err.message || 'Analyse impossible.');
    } finally {
      setLoading(false);
    }
  }, []);

  const runIncident = useCallback(async (incident) => {
    setOpen(true);
    setTitle(`ORION AI — ${incident.title}`);
    setLoading(true);
    setContent('');
    setError(null);
    try {
      const insight = await aiApi.analyzeIncident(incident.id);
      setContent(insight.content);
    } catch (err) {
      setError(err.message || 'Analyse impossible.');
    } finally {
      setLoading(false);
    }
  }, []);

  const close = useCallback(() => setOpen(false), []);

  return { open, title, loading, content, error, runAlert, runIncident, close };
}

export function AiInsightPanel({ insights, loading }) {
  if (loading) return <Spinner label="Chargement insights..." />;

  if (!insights?.length) {
    return (
      <p className="ai-insights-empty">
        Aucune analyse IA pour le moment. Les alertes critiques declenchent une analyse automatique.
      </p>
    );
  }

  return (
    <ul className="ai-insights-list">
      {insights.map((item) => (
        <li key={item.id} className="ai-insight-card">
          <div className="ai-insight-card__meta">
            <span className={`ai-insight-type ai-insight-type--${item.type}`}>
              {item.type?.replace('_', ' ')}
            </span>
            <time>{new Date(item.created_at).toLocaleString('fr-FR')}</time>
          </div>
          <h3>{item.title}</h3>
          <div className="ai-insight-card__content">
            <AiMarkdown>{item.content}</AiMarkdown>
          </div>
        </li>
      ))}
    </ul>
  );
}

export function AiChat() {
  const [messages, setMessages] = useState([
    {
      role: 'assistant',
      content:
        'Bonjour, je suis ORION AI. Posez-moi une question sur votre reseau (alertes, incidents, equipements offline…).',
    },
  ]);
  const [input, setInput] = useState('');
  const [busy, setBusy] = useState(false);
  const [error, setError] = useState(null);
  const bottomRef = useRef(null);

  useEffect(() => {
    bottomRef.current?.scrollIntoView({ behavior: 'smooth' });
  }, [messages, busy]);

  const handleSubmit = async (e) => {
    e.preventDefault();
    const text = input.trim();
    if (!text || busy) return;

    const userMsg = { role: 'user', content: text };
    const history = messages.filter((m) => m.role === 'user' || m.role === 'assistant');
    setMessages((prev) => [...prev, userMsg]);
    setInput('');
    setBusy(true);
    setError(null);

    try {
      const { reply } = await aiApi.sendChatMessage(text, history);
      setMessages((prev) => [...prev, { role: 'assistant', content: reply }]);
    } catch (err) {
      setError(err.message || 'Erreur ORION AI.');
    } finally {
      setBusy(false);
    }
  };

  return (
    <div className="ai-chat">
      <div className="ai-chat__messages">
        {messages.map((m, i) => (
          <div key={i} className={`ai-chat__bubble ai-chat__bubble--${m.role}`}>
            {m.role === 'assistant' ? <AiMarkdown>{m.content}</AiMarkdown> : m.content}
          </div>
        ))}
        {busy && <div className="ai-chat__bubble ai-chat__bubble--assistant ai-chat__typing">...</div>}
        <div ref={bottomRef} />
      </div>
      {error && <p className="ai-error">{error}</p>}
      <form className="ai-chat__form" onSubmit={handleSubmit}>
        <input
          type="text"
          value={input}
          onChange={(e) => setInput(e.target.value)}
          placeholder="Ex: Quelles alertes sont ouvertes ?"
          disabled={busy}
        />
        <button type="submit" className="btn btn--primary" disabled={busy || !input.trim()}>
          Envoyer
        </button>
      </form>
    </div>
  );
}
