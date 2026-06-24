import { createConfigStore } from './configStore.js';
import { AgentRunner } from './agentRunner.js';
import { detectLocalIp } from './browserIdentity.js';

const store = createConfigStore();
const runner = new AgentRunner(store, (status) => {
  window.dispatchEvent(new CustomEvent('orion-agent-status', { detail: status }));
});

window.orionAgent = {
  getStatus: () => Promise.resolve(runner.getStatus()),
  saveConfig: (partial) => Promise.resolve(runner.saveConfig(partial)),
  register: () => runner.register().then(() => ({ ok: true })),
  start: () => {
    runner.start();
    return Promise.resolve(runner.getStatus());
  },
  stop: () => {
    runner.stop();
    return Promise.resolve(runner.getStatus());
  },
  onStatus: (callback) => runner.onStatusChange(callback),
  detectLocalIp: () => detectLocalIp(),
};

const config = store.get('config', {});
if (config.apiKey && config.agentUuid) {
  runner.start();
}
