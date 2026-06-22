import Echo from 'laravel-echo';
import Pusher from 'pusher-js';
import { getStoredToken } from '../api/client';

/** Instance Echo singleton (initialisee apres login). */
let echoInstance = null;

/**
 * Cree ou retourne le client Laravel Echo connecte a Reverb.
 * @param {object} config — reponse GET /realtime/config
 */
export function getEcho(config) {
  if (!config?.enabled || !config?.key) {
    return null;
  }

  if (echoInstance) {
    return echoInstance;
  }

  window.Pusher = Pusher;

  // En dev Vite : proxy /broadcasting -> Laravel (evite CORS preflight).
  const authEndpoint = import.meta.env.DEV
    ? '/broadcasting/auth'
    : config.auth_endpoint;

  echoInstance = new Echo({
    broadcaster: 'reverb',
    key: config.key,
    wsHost: config.host,
    wsPort: config.port,
    wssPort: config.port,
    forceTLS: config.scheme === 'https',
    enabledTransports: ['ws', 'wss'],
    authEndpoint,
    auth: {
      headers: {
        Authorization: `Bearer ${getStoredToken()}`,
        Accept: 'application/json',
      },
    },
  });

  return echoInstance;
}

/** Deconnecte Echo (logout). */
export function disconnectEcho() {
  if (echoInstance) {
    echoInstance.disconnect();
    echoInstance = null;
  }
}

/** Emission globale pour rafraichir les pages ecoutantes. */
export function emitRealtimeEvent(type, payload = {}) {
  window.dispatchEvent(
    new CustomEvent('orion:realtime', { detail: { type, ...payload } }),
  );
}
