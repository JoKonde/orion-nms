import { useCallback, useEffect, useMemo, useState } from 'react';
import { getStoredToken, setStoredToken } from '../api/client';
import * as authApi from '../api/auth';
import { AuthContext } from './AuthContext';
import { disconnectEcho } from '../realtime/echoClient';

/**
 * AuthProvider — charge /auth/me au demarrage si un token existe deja.
 */
export function AuthProvider({ children }) {
  const [user, setUser] = useState(null);
  const [token, setToken] = useState(getStoredToken);
  const [loading, setLoading] = useState(!!getStoredToken());

  const refreshUser = useCallback(async () => {
    const me = await authApi.fetchMe();
    setUser(me);
    return me;
  }, []);

  useEffect(() => {
    if (!token) {
      setUser(null);
      setLoading(false);
      return;
    }

    let cancelled = false;

    (async () => {
      try {
        const me = await authApi.fetchMe();
        if (!cancelled) setUser(me);
      } catch {
        if (!cancelled) {
          setStoredToken(null);
          setUser(null);
          setToken(null);
        }
      } finally {
        if (!cancelled) setLoading(false);
      }
    })();

    return () => {
      cancelled = true;
    };
  }, [token]);

  const login = useCallback(async (email, password) => {
    const data = await authApi.login(email, password);
    setToken(data.token);
    // Recharge /auth/me pour avoir permissions completes (menu sidebar).
    const me = await authApi.fetchMe();
    setUser(me);
    return { ...data, user: me };
  }, []);

  const logout = useCallback(async () => {
    try {
      await authApi.logout();
    } finally {
      disconnectEcho();
      setToken(null);
      setUser(null);
    }
  }, []);

  const value = useMemo(
    () => ({
      user,
      token,
      loading,
      isAuthenticated: !!token && !!user,
      login,
      logout,
      refreshUser,
    }),
    [user, token, loading, login, logout, refreshUser],
  );

  return <AuthContext.Provider value={value}>{children}</AuthContext.Provider>;
}
