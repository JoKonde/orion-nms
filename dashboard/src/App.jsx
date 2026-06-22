import { BrowserRouter, Navigate, Route, Routes } from 'react-router-dom';
import { AuthProvider } from './auth/AuthProvider';
import { ProtectedRoute } from './auth/ProtectedRoute';
import { GuestRoute } from './auth/GuestRoute';
import { AdminLayout } from './layout/AdminLayout';
import { LoginPage } from './pages/LoginPage';
import { OverviewPage } from './pages/OverviewPage';
import { DevicesPage } from './pages/DevicesPage';
import { AgentsPage } from './pages/AgentsPage';
import { AlertsPage } from './pages/AlertsPage';
import { IncidentsPage } from './pages/IncidentsPage';
import { NetworkPage } from './pages/NetworkPage';
import { TopologyPage } from './pages/TopologyPage';
import { UsersPage } from './pages/UsersPage';
import { SoonPage } from './pages/SoonPage';
import { NotFoundPage } from './pages/NotFoundPage';

export default function App() {
  return (
    <AuthProvider>
      <BrowserRouter future={{ v7_startTransition: true, v7_relativeSplatPath: true }}>
        <Routes>
          {/* Route publique */}
          <Route
            path="/login"
            element={
              <GuestRoute>
                <LoginPage />
              </GuestRoute>
            }
          />

          {/* Routes protegees avec layout admin */}
          <Route element={<ProtectedRoute />}>
            <Route element={<AdminLayout />}>
              <Route index element={<OverviewPage />} />
              <Route path="devices" element={<DevicesPage />} />
              <Route path="agents" element={<AgentsPage />} />
              <Route path="alerts" element={<AlertsPage />} />
              <Route path="incidents" element={<IncidentsPage />} />
              <Route path="topology" element={<TopologyPage />} />
              <Route path="network" element={<NetworkPage />} />
              <Route path="users" element={<UsersPage />} />
              <Route
                path="ai"
                element={
                  <SoonPage title="ORION AI" moduleLabel="Module 10 — OpenRouter" />
                }
              />
              <Route
                path="reports"
                element={
                  <SoonPage title="Rapports" moduleLabel="Module 11 — Reports" />
                }
              />
            </Route>
          </Route>

          <Route path="/404" element={<NotFoundPage />} />
          <Route path="*" element={<Navigate to="/404" replace />} />
        </Routes>
      </BrowserRouter>
    </AuthProvider>
  );
}
