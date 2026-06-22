import { apiClient } from './client';
import { unwrapPaginated, unwrapResource } from '../utils/apiHelpers';

export async function fetchDevices(params = {}) {
  const { data } = await apiClient.get('/devices', { params });
  return unwrapPaginated(data);
}

export async function fetchDevice(id) {
  const { data } = await apiClient.get(`/devices/${id}`);
  return unwrapResource(data);
}

export async function createDevice(payload) {
  const { data } = await apiClient.post('/devices', payload);
  return unwrapResource(data);
}

export async function updateDevice(id, payload) {
  const { data } = await apiClient.patch(`/devices/${id}`, payload);
  return unwrapResource(data);
}

export async function deleteDevice(id) {
  await apiClient.delete(`/devices/${id}`);
}
