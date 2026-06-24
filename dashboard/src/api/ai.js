import { apiClient } from './client';
import { unwrapPaginated, unwrapResource } from '../utils/apiHelpers';

export async function fetchAiStatus() {
  const { data } = await apiClient.get('/ai/status');
  return data;
}

export async function sendChatMessage(message, history = []) {
  const { data } = await apiClient.post('/ai/chat', { message, history });
  return data;
}

export async function analyzeAlert(alertId) {
  const { data } = await apiClient.post(`/ai/analyze/alert/${alertId}`);
  return unwrapResource(data.insight ?? data);
}

export async function analyzeIncident(incidentId) {
  const { data } = await apiClient.post(`/ai/analyze/incident/${incidentId}`);
  return unwrapResource(data.insight ?? data);
}

export async function fetchAiInsights(limit = 20) {
  const { data } = await apiClient.get('/ai/insights', { params: { limit } });
  return unwrapPaginated(data).items;
}
