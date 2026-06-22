import { AGENT_VERSION, createOrionClient, ensureAgentUuid } from './orionApi.js';
import { collectMetrics, collectSystemIdentity } from './systemMetrics.js';

const HEARTBEAT_MS = 60_000;

/**
 * Boucle agent : enregistrement, heartbeat et envoi metriques.
 */
export class AgentRunner {
  constructor(store, onStatus) {
    this.store = store;
    this.onStatus = onStatus;
    this.timer = null;
    this.running = false;
    this.lastError = null;
    this.lastSyncAt = null;
  }

  getStatus() {
    const config = this.store.get('config', {});
    return {
      running: this.running,
      registered: Boolean(config.apiKey && config.agentUuid),
      lastError: this.lastError,
      lastSyncAt: this.lastSyncAt,
      config: {
        apiBaseUrl: config.apiBaseUrl || 'http://localhost:8001/api/v1',
        bootstrapKey: config.bootstrapKey ? '••••••' : '',
        agentUuid: config.agentUuid || null,
        hostname: config.hostname || null,
      },
    };
  }

  async register() {
    const config = this.store.get('config', {});
    const apiBaseUrl = config.apiBaseUrl || 'http://localhost:8001/api/v1';
    const bootstrapKey = config.bootstrapKey;

    if (!bootstrapKey) {
      throw new Error('Cle bootstrap ORION requise.');
    }

    const identity = await collectSystemIdentity();
    const agentUuid = ensureAgentUuid(config);
    const client = createOrionClient(apiBaseUrl);

    const result = await client.register(
      {
        ...identity,
        agent_version: AGENT_VERSION,
        agent_uuid: agentUuid,
      },
      bootstrapKey,
    );

    const next = {
      ...config,
      apiBaseUrl,
      bootstrapKey,
      agentUuid,
      apiKey: result.api_key,
      hostname: identity.hostname,
    };
    this.store.set('config', next);
    this.lastError = null;
    this.emit();

    return result;
  }

  start() {
    if (this.running) return;
    this.running = true;
    this.tick();
    this.timer = setInterval(() => this.tick(), HEARTBEAT_MS);
    this.emit();
  }

  stop() {
    this.running = false;
    if (this.timer) {
      clearInterval(this.timer);
      this.timer = null;
    }
    this.emit();
  }

  async tick() {
    const config = this.store.get('config', {});

    if (!config.apiKey || !config.agentUuid) {
      this.lastError = 'Agent non enregistre.';
      this.emit();
      return;
    }

    try {
      const client = createOrionClient(config.apiBaseUrl);
      const metrics = await collectMetrics();

      await client.heartbeat(config.agentUuid, config.apiKey, {
        uptime: metrics.find((m) => m.type === 'uptime')?.value ?? 0,
      });
      await client.sendMetrics(config.agentUuid, config.apiKey, metrics);

      this.lastError = null;
      this.lastSyncAt = new Date().toISOString();
    } catch (err) {
      this.lastError = err.response?.data?.message || err.message || 'Erreur sync';
    }

    this.emit();
  }

  saveConfig(partial) {
    const config = { ...this.store.get('config', {}), ...partial };
    if (partial.agentUuid === '') {
      delete config.agentUuid;
      delete config.apiKey;
    }
    this.store.set('config', config);
    this.emit();
    return config;
  }

  emit() {
    this.onStatus?.(this.getStatus());
  }
}
