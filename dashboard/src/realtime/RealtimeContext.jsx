import { createContext, useContext } from 'react';

export const RealtimeContext = createContext(null);

export function useRealtime() {
  const ctx = useContext(RealtimeContext);
  if (!ctx) {
    throw new Error('useRealtime doit etre utilise dans RealtimeProvider.');
  }
  return ctx;
}
