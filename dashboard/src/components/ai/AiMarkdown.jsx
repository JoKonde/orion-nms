import ReactMarkdown from 'react-markdown';

/**
 * Affiche le texte brut renvoye par ORION AI en Markdown lisible
 * (titres ###, gras **, listes, etc.).
 */
export function AiMarkdown({ children, className = '' }) {
  if (!children) return null;

  const text = typeof children === 'string' ? children.trim() : String(children);

  return (
    <div className={['ai-markdown', className].filter(Boolean).join(' ')}>
      <ReactMarkdown>{text}</ReactMarkdown>
    </div>
  );
}
