import { Navigate, Outlet, useLocation } from 'react-router-dom';
import { useAuth } from './AuthContext';

/**
 * ProtectedRoute — redirige vers /login si pas de session.
 * Option permission : masque la route si l'utilisateur n'a pas le droit.
 */
export function ProtectedRoute({ permission = null }) {
  const { isAuthenticated, loading, user } = useAuth();
  const location = useLocation();

  if (loading) {
    return (
      <div className="app-loading">
        <div className="spinner" aria-hidden="true" />
        <p>Chargement de la session...</p>
      </div>
    );
  }

  if (!isAuthenticated) {
    return <Navigate to="/login" state={{ from: location }} replace />;
  }

  if (permission && user?.permissions && !user.permissions.includes(permission)) {
    return <Navigate to="/" replace />;
  }

  return <Outlet />;
}
