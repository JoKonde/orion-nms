import { useState } from 'react';
import { useNavigate, useLocation } from 'react-router-dom';
import { useAuth } from '../auth/AuthContext';

/**
 * LoginPage — connexion admin via Sanctum (POST /auth/login).
 */
export function LoginPage() {
  const { login } = useAuth();
  const navigate = useNavigate();
  const location = useLocation();
  const from = location.state?.from?.pathname || '/';

  const [email, setEmail] = useState('admin@orion.local');
  const [password, setPassword] = useState('');
  const [error, setError] = useState('');
  const [submitting, setSubmitting] = useState(false);

  const handleSubmit = async (e) => {
    e.preventDefault();
    setError('');
    setSubmitting(true);

    try {
      await login(email, password);
      navigate(from, { replace: true });
    } catch (err) {
      setError(err.message || 'Identifiants invalides.');
    } finally {
      setSubmitting(false);
    }
  };

  return (
    <div className="login-page">
      <div className="login-card">
        <div className="login-card__brand">
          <img src="/orion.svg" alt="" width={48} height={48} />
          <h1>ORION</h1>
          <p>Network Management System</p>
        </div>

        <form className="login-form" onSubmit={handleSubmit}>
          <h2>Connexion admin</h2>

          {error && (
            <div className="login-form__error" role="alert">
              {error}
            </div>
          )}

          <label className="form-field">
            <span>Email</span>
            <input
              type="email"
              value={email}
              onChange={(e) => setEmail(e.target.value)}
              autoComplete="email"
              required
              placeholder="admin@orion.local"
            />
          </label>

          <label className="form-field">
            <span>Mot de passe</span>
            <input
              type="password"
              value={password}
              onChange={(e) => setPassword(e.target.value)}
              autoComplete="current-password"
              required
              placeholder="••••••••"
            />
          </label>

          <button type="submit" className="btn btn--primary btn--block" disabled={submitting}>
            {submitting ? 'Connexion...' : 'Se connecter'}
          </button>
        </form>

        <p className="login-card__hint">
          Compte seed : <code>admin@orion.local</code> / <code>Password123!</code>
        </p>
      </div>
    </div>
  );
}
