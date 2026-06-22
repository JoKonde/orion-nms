import { useEffect } from 'react';

/**
 * Ecoute les events orion:realtime et declenche onRefresh si le type correspond.
 * @param {string|string[]} eventTypes — ex: 'alert.raised' ou ['topology.updated', 'all']
 */
export function useRealtimeRefresh(eventTypes, onRefresh) {
  const types = Array.isArray(eventTypes) ? eventTypes : [eventTypes];
  const key = types.join(',');

  useEffect(() => {
    if (!onRefresh) return;

    const handler = (e) => {
      const type = e.detail?.type;
      if (types.includes(type) || types.includes('all')) {
        onRefresh();
      }
    };

    window.addEventListener('orion:realtime', handler);
    return () => window.removeEventListener('orion:realtime', handler);
  }, [key, onRefresh]);
}
