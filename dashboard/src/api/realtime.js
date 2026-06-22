import { apiClient } from './client';

/** GET /realtime/config — parametres Echo/Reverb. */
export async function fetchRealtimeConfig() {
  const { data } = await apiClient.get('/realtime/config');
  return data;
}
