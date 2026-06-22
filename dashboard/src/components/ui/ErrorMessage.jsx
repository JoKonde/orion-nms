/** ErrorMessage — affichage erreur API avec bouton retry optionnel. */
export function ErrorMessage({ message, onRetry }) {
  return (
    <div className="error-message" role="alert">
      <p>{message}</p>
      {onRetry && (
        <button type="button" className="btn btn--secondary" onClick={onRetry}>
          Reessayer
        </button>
      )}
    </div>
  );
}
