/**
 * PlaceholderPage — page temporaire en attendant les phases CRUD (Phase 3+).
 */
export function PlaceholderPage({ title, description, apiHint }) {
  return (
    <div className="page placeholder-page">
      <div className="placeholder-page__inner">
        <span className="placeholder-page__icon" aria-hidden="true">
          ◌
        </span>
        <h2>{title}</h2>
        <p>{description}</p>
        {apiHint && (
          <p className="placeholder-page__api">
            API : <code>{apiHint}</code>
          </p>
        )}
        <span className="placeholder-page__badge">Phase 3 — a venir</span>
      </div>
    </div>
  );
}
