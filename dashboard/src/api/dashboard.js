import { apiClient } from './client';

/** GET /dashboard/overview — KPIs agreges reseau. */
export async function fetchOverview() {
  const { data } = await apiClient.get('/dashboard/overview');
  return data;
}

/** GET /dashboard/health — score sante detaille + facteurs. */
export async function fetchHealth() {
  const { data } = await apiClient.get('/dashboard/health');
  return data;
}
