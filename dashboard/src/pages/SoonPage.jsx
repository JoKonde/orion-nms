import { Link } from 'react-router-dom';

/** Page modules futurs (10 / 11). */
export function SoonPage({ title, moduleLabel }) {
  return (
    <div className="page placeholder-page">
      <div className="placeholder-page__inner">
        <span className="placeholder-page__icon" aria-hidden="true">
          ✦
        </span>
        <h2>{title}</h2>
        <p>
          Cette section sera disponible avec le <strong>{moduleLabel}</strong> du backend
          Laravel.
        </p>
        <Link to="/" className="btn btn--secondary">
          Retour a l&apos;accueil
        </Link>
      </div>
    </div>
  );
}
