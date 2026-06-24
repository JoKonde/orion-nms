import { useCallback, useEffect, useRef, useState } from 'react';
import * as dashboardApi from '../api/dashboard';

/**
 * Hook — charge l'overview dashboard (KPIs + sante + facteurs en un seul appel).
 */
export function useDashboard() {
  const [overview, setOverview] = useState(null);
  const [loading, setLoading] = useState(true);
  const [refreshing, setRefreshing] = useState(false);
  const [error, setError] = useState(null);
  const hasLoadedRef = useRef(false);

  const load = useCallback(async (fresh = false) => {
    setError(null);

    if (!hasLoadedRef.current) {
      setLoading(true);
    } else {
      setRefreshing(true);
    }

    try {
      const overviewData = await dashboardApi.fetchOverview({ fresh });
      setOverview(overviewData);
      hasLoadedRef.current = true;
    } catch (err) {
      setError(err.message || 'Impossible de charger le tableau de bord.');
    } finally {
      setLoading(false);
      setRefreshing(false);
    }
  }, []);

  useEffect(() => {
    load(false);
  }, [load]);

  const refresh = useCallback(() => load(true), [load]);

  return { overview, loading, refreshing, error, refresh };
}
