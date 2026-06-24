import { apiClient } from './client';

/** GET /dashboard/overview — KPIs agreges reseau (+ sante et facteurs). */
export async function fetchOverview({ fresh = false } = {}) {
  const { data } = await apiClient.get('/dashboard/overview', {
    params: fresh ? { fresh: 1 } : undefined,
  });
  return data;
}

/** GET /dashboard/health — score sante detaille (legacy, overview suffit). */
export async function fetchHealth({ fresh = false } = {}) {
  const { data } = await apiClient.get('/dashboard/health', {
    params: fresh ? { fresh: 1 } : undefined,
  });
  return data;
}
