import { apiClient, setStoredToken } from './client';

/**
 * Auth API — login, logout, profil utilisateur connecte.
 */
export async function login(email, password) {
  const { data } = await apiClient.post('/auth/login', { email, password });
  setStoredToken(data.token);
  return data;
}

export async function logout() {
  try {
    await apiClient.post('/auth/logout');
  } finally {
    setStoredToken(null);
  }
}

/** GET /auth/me — roles + permissions pour le menu sidebar. */
export async function fetchMe() {
  const { data } = await apiClient.get('/auth/me');
  return data.data ?? data;
}
