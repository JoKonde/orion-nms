import { apiClient } from './client';

/** GET /topology — graphe Cytoscape (nodes + edges). */
export async function fetchTopology() {
  const { data } = await apiClient.get('/topology');
  return data;
}

/** POST /topology/rebuild — reconstruit les liens reseau. */
export async function rebuildTopology() {
  const { data } = await apiClient.post('/topology/rebuild');
  return data;
}
