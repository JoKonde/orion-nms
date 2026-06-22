import { apiClient } from './client';
import { unwrapPaginated, unwrapResource } from '../utils/apiHelpers';

export async function fetchUsers(params = {}) {
  const { data } = await apiClient.get('/users', { params: { per_page: 15, ...params } });
  return unwrapPaginated(data);
}

export async function createUser(payload) {
  const { data } = await apiClient.post('/users', payload);
  return unwrapResource(data);
}

export async function updateUser(id, payload) {
  const { data } = await apiClient.patch(`/users/${id}`, payload);
  return unwrapResource(data);
}

export async function deleteUser(id) {
  await apiClient.delete(`/users/${id}`);
}
