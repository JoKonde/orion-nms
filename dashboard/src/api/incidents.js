import { apiClient } from './client';
import { unwrapPaginated, unwrapResource } from '../utils/apiHelpers';

export async function fetchIncidents(params = {}) {
  const { data } = await apiClient.get('/incidents', { params });
  return unwrapPaginated(data);
}

export async function fetchIncident(id) {
  const { data } = await apiClient.get(`/incidents/${id}`);
  return unwrapResource(data);
}

export async function createIncident(payload) {
  const { data } = await apiClient.post('/incidents', payload);
  return unwrapResource(data);
}

export async function updateIncident(id, payload) {
  const { data } = await apiClient.patch(`/incidents/${id}`, payload);
  return unwrapResource(data);
}

export async function deleteIncident(id) {
  await apiClient.delete(`/incidents/${id}`);
}

export async function assignIncident(id, assignedTo) {
  const { data } = await apiClient.post(`/incidents/${id}/assign`, {
    assigned_to: assignedTo,
  });
  return unwrapResource(data);
}

export async function startIncident(id) {
  const { data } = await apiClient.post(`/incidents/${id}/start`);
  return unwrapResource(data);
}

export async function resolveIncident(id, resolutionNotes = '') {
  const { data } = await apiClient.post(`/incidents/${id}/resolve`, {
    resolution_notes: resolutionNotes || undefined,
  });
  return unwrapResource(data);
}

export async function closeIncident(id) {
  const { data } = await apiClient.post(`/incidents/${id}/close`);
  return unwrapResource(data);
}
