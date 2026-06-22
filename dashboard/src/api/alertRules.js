import { apiClient } from './client';
import { unwrapPaginated, unwrapResource } from '../utils/apiHelpers';

export async function fetchAlertRules(params = {}) {
  const { data } = await apiClient.get('/alert-rules', { params });
  return unwrapPaginated(data);
}

export async function createAlertRule(payload) {
  const { data } = await apiClient.post('/alert-rules', payload);
  return unwrapResource(data);
}

export async function updateAlertRule(id, payload) {
  const { data } = await apiClient.patch(`/alert-rules/${id}`, payload);
  return unwrapResource(data);
}

export async function deleteAlertRule(id) {
  await apiClient.delete(`/alert-rules/${id}`);
}
