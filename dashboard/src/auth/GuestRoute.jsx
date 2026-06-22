import { Navigate, useLocation } from 'react-router-dom';
import { useAuth } from './AuthContext';

/** Redirige vers / si l'utilisateur est deja connecte (page login). */
export function GuestRoute({ children }) {
  const { isAuthenticated, loading } = useAuth();
  const location = useLocation();
  const from = location.state?.from?.pathname || '/';

  if (loading) {
    return (
      <div className="app-loading">
        <div className="spinner" aria-hidden="true" />
        <p>Chargement...</p>
      </div>
    );
  }

  if (isAuthenticated) {
    return <Navigate to={from} replace />;
  }

  return children;
}
