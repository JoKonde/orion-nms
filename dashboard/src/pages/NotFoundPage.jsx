import { Link } from 'react-router-dom';

export function NotFoundPage() {
  return (
    <div className="page placeholder-page">
      <div className="placeholder-page__inner">
        <h2>404 — Page introuvable</h2>
        <p>La route demandee n&apos;existe pas dans ORION Dashboard.</p>
        <Link to="/" className="btn btn--primary">
          Retour a l&apos;accueil
        </Link>
      </div>
    </div>
  );
}
