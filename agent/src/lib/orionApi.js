import axios from 'axios';
import { v4 as uuidv4 } from 'uuid';

export const AGENT_VERSION = '1.0.0';

export function createOrionClient(baseUrl) {
  const api = axios.create({
    baseURL: baseUrl.replace(/\/$/, ''),
    headers: {
      Accept: 'application/json',
      'Content-Type': 'application/json',
    },
    timeout: 30000,
  });

  return {
    async register(payload, bootstrapKey) {
      const { data } = await api.post('/agents/register', payload, {
        headers: { 'X-Orion-Bootstrap-Key': bootstrapKey },
      });
      return data;
    },

    async heartbeat(agentUuid, apiKey, payload = {}) {
      const { data } = await api.post(
        '/agents/heartbeat',
        { agent_uuid: agentUuid, payload },
        { headers: agentHeaders(agentUuid, apiKey) },
      );
      return data;
    },

    async sendMetrics(agentUuid, apiKey, batch) {
      const { data } = await api.post(
        '/agents/metrics',
        { agent_uuid: agentUuid, batch },
        { headers: agentHeaders(agentUuid, apiKey) },
      );
      return data;
    },
  };
}

function agentHeaders(agentUuid, apiKey) {
  return {
    'X-Agent-UUID': agentUuid,
    'X-Agent-Api-Key': apiKey,
    Authorization: `Bearer ${apiKey}`,
  };
}

export function ensureAgentUuid(config) {
  if (config.agentUuid) {
    return config.agentUuid;
  }
  return uuidv4();
}
