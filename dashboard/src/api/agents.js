import { apiClient } from './client';
import { unwrapPaginated, unwrapResource } from '../utils/apiHelpers';

export async function fetchAgents(params = {}) {
  const { data } = await apiClient.get('/agents', { params });
  return unwrapPaginated(data);
}

export async function fetchAgent(id) {
  const { data } = await apiClient.get(`/agents/${id}`);
  return unwrapResource(data);
}

export async function deleteAgent(id) {
  await apiClient.delete(`/agents/${id}`);
}
