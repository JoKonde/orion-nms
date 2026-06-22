import { useState } from 'react';
import { useNavigate } from 'react-router-dom';
import { useAuth } from '../auth/AuthContext';
import { NotificationBell } from '../components/layout/NotificationBell';

/** Barre superieure : titre de page + menu utilisateur (deconnexion). */
export function Header({ title }) {
  const { user, logout } = useAuth();
  const navigate = useNavigate();
  const [menuOpen, setMenuOpen] = useState(false);

  const handleLogout = async () => {
    await logout();
    navigate('/login', { replace: true });
  };

  const roleLabel = user?.roles?.[0] ?? 'utilisateur';

  return (
    <header className="header">
      <div className="header__left">
        <button
          type="button"
          className="header__menu-btn"
          aria-label="Ouvrir le menu"
          onClick={() => document.body.classList.toggle('sidebar-open')}
        >
          ☰
        </button>
        <h1 className="header__title">{title}</h1>
      </div>

      <div className="header__right">
        <NotificationBell />
        <div className="header__user">
          <button
            type="button"
            className="header__user-btn"
            onClick={() => setMenuOpen((v) => !v)}
            aria-expanded={menuOpen}
          >
            <span className="header__avatar" aria-hidden="true">
              {(user?.name ?? '?').charAt(0).toUpperCase()}
            </span>
            <span className="header__user-info">
              <strong>{user?.name}</strong>
              <small>{roleLabel}</small>
            </span>
          </button>

          {menuOpen && (
            <div className="header__dropdown">
              <p className="header__dropdown-email">{user?.email}</p>
              <button type="button" className="header__dropdown-logout" onClick={handleLogout}>
                Deconnexion
              </button>
            </div>
          )}
        </div>
      </div>
    </header>
  );
}
