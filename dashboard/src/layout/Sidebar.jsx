import { NavLink } from 'react-router-dom';
import { NAV_SECTIONS } from '../config/navigation';
import { hasPermission } from '../utils/permissions';
import { useAuth } from '../auth/AuthContext';

/** Un lien du menu lateral — surligne si route active. */
export function SidebarItem({ item }) {
  const className = ({ isActive }) =>
    `sidebar__item${isActive ? ' sidebar__item--active' : ''}${item.soon ? ' sidebar__item--soon' : ''}`;

  return (
    <NavLink to={item.path} className={className} end={item.path === '/'}>
      <span className="sidebar__icon" aria-hidden="true">
        {item.icon}
      </span>
      <span className="sidebar__label">{item.label}</span>
      {item.soon && <span className="sidebar__badge">Soon</span>}
    </NavLink>
  );
}

/** Menu lateral gauche — filtre les items selon permissions Spatie. */
export function Sidebar() {
  const { user } = useAuth();
  const appName = import.meta.env.VITE_APP_NAME || 'ORION';

  return (
    <aside className="sidebar" aria-label="Navigation principale">
      <div className="sidebar__brand">
        <img src="/orion.svg" alt="" className="sidebar__logo" width={28} height={28} />
        <div>
          <strong className="sidebar__title">{appName}</strong>
          <span className="sidebar__subtitle">Network Management</span>
        </div>
      </div>

      <nav className="sidebar__nav">
        {NAV_SECTIONS.map((section) => {
          const visibleItems = section.items.filter((item) => {
            if (item.soon) return true;
            if (!item.permission) return true;
            return hasPermission(user, item.permission);
          });

          if (visibleItems.length === 0) return null;

          return (
            <div key={section.label} className="sidebar__section">
              <p className="sidebar__section-label">{section.label}</p>
              {visibleItems.map((item) => (
                <SidebarItem key={item.id} item={item} />
              ))}
            </div>
          );
        })}
      </nav>

      <footer className="sidebar__footer">
        <span>ORION Dashboard v0.1</span>
      </footer>
    </aside>
  );
}
