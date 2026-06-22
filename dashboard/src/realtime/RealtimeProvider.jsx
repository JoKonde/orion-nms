import { useCallback, useEffect, useMemo, useState } from 'react';
import { useAuth } from '../auth/AuthContext';
import { fetchRealtimeConfig } from '../api/realtime';
import { disconnectEcho, emitRealtimeEvent, getEcho } from './echoClient';
import { RealtimeContext } from './RealtimeContext';
import { hasPermission } from '../utils/permissions';

const MAX_NOTIFICATIONS = 30;

/** Labels francais pour les events Reverb. */
const EVENT_LABELS = {
  'alert.raised': 'Nouvelle alerte',
  'incident.updated': 'Incident mis a jour',
  'device.discovered': 'Equipement decouvert',
  'agent.status.changed': 'Statut agent modifie',
  'metric.received': 'Metrique recue',
  'topology.updated': 'Topologie mise a jour',
};

/**
 * RealtimeProvider — init Echo + abonnements canaux prives ORION.
 */
export function RealtimeProvider({ children }) {
  const { isAuthenticated, user } = useAuth();
  const [enabled, setEnabled] = useState(false);
  const [connected, setConnected] = useState(false);
  const [notifications, setNotifications] = useState([]);

  const pushNotification = useCallback((entry) => {
    setNotifications((prev) => [entry, ...prev].slice(0, MAX_NOTIFICATIONS));
  }, []);

  const clearNotifications = useCallback(() => {
    setNotifications([]);
  }, []);

  const subscribeChannel = useCallback(
    (echo, channelName, eventName) => {
      echo
        .private(channelName)
        .listen(`.${eventName}`, (payload) => {
          const entry = {
            id: `${eventName}-${Date.now()}-${Math.random().toString(36).slice(2, 7)}`,
            type: eventName,
            label: EVENT_LABELS[eventName] ?? eventName,
            payload,
            at: new Date().toISOString(),
          };
          pushNotification(entry);
          emitRealtimeEvent(eventName, { payload });
        });
    },
    [pushNotification],
  );

  useEffect(() => {
    if (!isAuthenticated || !user) {
      disconnectEcho();
      setEnabled(false);
      setConnected(false);
      return;
    }

    let cancelled = false;

    (async () => {
      try {
        const config = await fetchRealtimeConfig();
        if (cancelled) return;

        if (!config.enabled) {
          setEnabled(false);
          return;
        }

        const echo = getEcho(config);
        if (!echo || cancelled) return;

        setEnabled(true);
        setConnected(true);

        const channels = config.channels ?? {};

        if (hasPermission(user, 'alerts.view') && channels.alerts) {
          subscribeChannel(echo, channels.alerts, 'alert.raised');
        }
        if (hasPermission(user, 'incidents.view') && channels.incidents) {
          subscribeChannel(echo, channels.incidents, 'incident.updated');
        }
        if (hasPermission(user, 'devices.view') && channels.devices) {
          subscribeChannel(echo, channels.devices, 'device.discovered');
        }
        if (hasPermission(user, 'agents.view') && channels.agents) {
          subscribeChannel(echo, channels.agents, 'agent.status.changed');
        }
        if (hasPermission(user, 'topology.view') && channels.topology) {
          subscribeChannel(echo, channels.topology, 'topology.updated');
        }
      } catch {
        if (!cancelled) {
          setEnabled(false);
          setConnected(false);
        }
      }
    })();

    return () => {
      cancelled = true;
    };
  }, [isAuthenticated, user, subscribeChannel]);

  useEffect(() => {
    if (!isAuthenticated) {
      disconnectEcho();
      setNotifications([]);
    }
  }, [isAuthenticated]);

  const value = useMemo(
    () => ({
      enabled,
      connected,
      notifications,
      unreadCount: notifications.length,
      clearNotifications,
    }),
    [enabled, connected, notifications, clearNotifications],
  );

  return <RealtimeContext.Provider value={value}>{children}</RealtimeContext.Provider>;
}
