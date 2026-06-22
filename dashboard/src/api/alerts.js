import { apiClient } from './client';
import { unwrapPaginated, unwrapResource } from '../utils/apiHelpers';

export async function fetchAlerts(params = {}) {
  const { data } = await apiClient.get('/alerts', { params });
  return unwrapPaginated(data);
}

export async function fetchAlert(id) {
  const { data } = await apiClient.get(`/alerts/${id}`);
  return unwrapResource(data);
}

export async function acknowledgeAlert(id) {
  const { data } = await apiClient.post(`/alerts/${id}/acknowledge`);
  return unwrapResource(data);
}

export async function resolveAlert(id) {
  const { data } = await apiClient.post(`/alerts/${id}/resolve`);
  return unwrapResource(data);
}

export async function escalateAlert(id) {
  const { data } = await apiClient.post(`/alerts/${id}/escalate`);
  return unwrapResource(data);
}
