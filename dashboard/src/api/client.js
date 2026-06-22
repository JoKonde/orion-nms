/**
 * Client HTTP centralise — toutes les requetes vers l'API ORION passent ici.
 * Le token Sanctum est injecte automatiquement via l'intercepteur.
 */
import axios from 'axios';

const TOKEN_KEY = 'orion_auth_token';

export const apiClient = axios.create({
  baseURL: import.meta.env.VITE_API_URL || 'http://localhost:8001/api/v1',
  headers: {
    Accept: 'application/json',
    'Content-Type': 'application/json',
  },
});

/** Lit le token stocke en localStorage. */
export function getStoredToken() {
  return localStorage.getItem(TOKEN_KEY);
}

/** Enregistre ou supprime le token Sanctum. */
export function setStoredToken(token) {
  if (token) {
    localStorage.setItem(TOKEN_KEY, token);
  } else {
    localStorage.removeItem(TOKEN_KEY);
  }
}

/** Intercepteur : ajoute Authorization Bearer si token present. */
apiClient.interceptors.request.use((config) => {
  const token = getStoredToken();
  if (token) {
    config.headers.Authorization = `Bearer ${token}`;
  }
  return config;
});

/**
 * Intercepteur reponse : sur 401, on purge la session et on redirige vers login.
 * (Evite de rester bloque avec un token expire.)
 */
apiClient.interceptors.response.use(
  (response) => response,
  (error) => {
    if (error.response?.status === 401 && !error.config?.url?.includes('/auth/login')) {
      setStoredToken(null);
      if (window.location.pathname !== '/login') {
        window.location.href = '/login';
      }
    }
    return Promise.reject(normalizeApiError(error));
  },
);

/** Formate les erreurs Laravel en objet simple pour l'UI. */
function normalizeApiError(error) {
  const data = error.response?.data;
  return {
    status: error.response?.status ?? 0,
    message:
      data?.message ||
      (data?.errors ? Object.values(data.errors).flat().join(' ') : null) ||
      error.message ||
      'Erreur reseau.',
    errors: data?.errors ?? {},
  };
}

export { TOKEN_KEY };
