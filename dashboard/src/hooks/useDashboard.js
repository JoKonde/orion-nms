import { useCallback, useEffect, useState } from 'react';
import * as dashboardApi from '../api/dashboard';

/**
 * Hook — charge overview + health en parallele pour la page d'accueil.
 */
export function useDashboard() {
  const [overview, setOverview] = useState(null);
  const [health, setHealth] = useState(null);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState(null);

  const load = useCallback(async () => {
    setLoading(true);
    setError(null);
    try {
      const [overviewData, healthData] = await Promise.all([
        dashboardApi.fetchOverview(),
        dashboardApi.fetchHealth(),
      ]);
      setOverview(overviewData);
      setHealth(healthData);
    } catch (err) {
      setError(err.message || 'Impossible de charger le tableau de bord.');
    } finally {
      setLoading(false);
    }
  }, []);

  useEffect(() => {
    load();
  }, [load]);

  return { overview, health, loading, error, refresh: load };
}
