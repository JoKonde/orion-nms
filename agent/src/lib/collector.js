import { collectBrowserIdentity } from './browserIdentity.js';
import { collectBrowserMetrics } from './browserMetrics.js';

/**
 * Identite : Electron natif (systeminformation) ou navigateur.
 * @param {{ localIp?: string, hostname?: string }} [overrides]
 */
export async function collectIdentity(overrides = {}) {
  if (window.orionNative?.collectIdentity) {
    return window.orionNative.collectIdentity();
  }
  return collectBrowserIdentity(overrides);
}

/** Metriques : Electron natif ou navigateur. */
export async function collectMetrics() {
  if (window.orionNative?.collectMetrics) {
    return window.orionNative.collectMetrics();
  }
  return collectBrowserMetrics();
}
