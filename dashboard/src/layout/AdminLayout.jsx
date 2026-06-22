import { Outlet, useLocation } from 'react-router-dom';
import { Sidebar } from './Sidebar';
import { Header } from './Header';
import { NAV_SECTIONS } from '../config/navigation';
import { RealtimeProvider } from '../realtime/RealtimeProvider';

/** Resout le titre de page depuis la config navigation. */
function resolvePageTitle(pathname) {
  for (const section of NAV_SECTIONS) {
    for (const item of section.items) {
      if (item.path === pathname) return item.label;
    }
  }
  if (pathname === '/login') return 'Connexion';
  return 'ORION';
}

/** Layout admin : sidebar fixe + header + zone contenu (Outlet). */
export function AdminLayout() {
  const { pathname } = useLocation();
  const title = resolvePageTitle(pathname);

  return (
    <RealtimeProvider>
      <div className="admin-layout">
        <Sidebar />
        <div className="admin-layout__main">
          <Header title={title} />
          <main className="admin-layout__content">
            <Outlet />
          </main>
        </div>
      </div>
    </RealtimeProvider>
  );
}
