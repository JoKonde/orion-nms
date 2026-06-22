/** Spinner — indicateur de chargement reutilisable. */
export function Spinner({ label = 'Chargement...' }) {
  return (
    <div className="spinner-block" role="status">
      <div className="spinner" aria-hidden="true" />
      {label && <p>{label}</p>}
    </div>
  );
}
