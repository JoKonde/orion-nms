import { createContext, useContext } from 'react';

/**
 * AuthContext — stocke user, token, etat de chargement pour toute l'app.
 */
export const AuthContext = createContext(null);

export function useAuth() {
  const ctx = useContext(AuthContext);
  if (!ctx) {
    throw new Error('useAuth doit etre utilise dans un AuthProvider.');
  }
  return ctx;
}
