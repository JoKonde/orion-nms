import { useState } from 'react';
import { useRealtime } from '../../realtime/RealtimeContext';
import { formatDate } from '../../utils/format';

/** Cloche notifications temps reel (header). */
export function NotificationBell() {
  const { enabled, connected, notifications, unreadCount, clearNotifications } = useRealtime();
  const [open, setOpen] = useState(false);

  if (!enabled) {
    return (
      <span className="notification-bell notification-bell--off" title="Reverb desactive">
        ◌
      </span>
    );
  }

  return (
    <div className="notification-bell">
      <button
        type="button"
        className="notification-bell__btn"
        onClick={() => setOpen((v) => !v)}
        aria-expanded={open}
        title={connected ? 'Notifications live' : 'Connexion Reverb...'}
      >
        🔔
        {unreadCount > 0 && (
          <span className="notification-bell__count">{unreadCount > 9 ? '9+' : unreadCount}</span>
        )}
        <span className={`notification-bell__dot${connected ? ' notification-bell__dot--on' : ''}`} />
      </button>

      {open && (
        <div className="notification-bell__panel">
          <header className="notification-bell__panel-header">
            <strong>Temps reel</strong>
            {notifications.length > 0 && (
              <button type="button" className="notification-bell__clear" onClick={clearNotifications}>
                Tout effacer
              </button>
            )}
          </header>
          {notifications.length === 0 ? (
            <p className="notification-bell__empty">Aucune notification.</p>
          ) : (
            <ul className="notification-bell__list">
              {notifications.map((n) => (
                <li key={n.id}>
                  <strong>{n.label}</strong>
                  <small>{formatDate(n.at)}</small>
                </li>
              ))}
            </ul>
          )}
        </div>
      )}
    </div>
  );
}
