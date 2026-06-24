import { apiClient } from './client';
import { unwrapPaginated } from '../utils/apiHelpers';

const LATEST_TYPES = [
  'cpu',
  'ram',
  'ram_total',
  'swap_usage',
  'disk',
  'disk_usage',
  'network_in',
  'network_out',
  'temperature',
  'uptime',
];

const RANGE_CONFIG = {
  '24h': { hours: 24, granularity: 'raw', limit: 500 },
  '7d': { hours: 24 * 7, granularity: 'hourly', fallbackRaw: true, rawLimit: 3000 },
  '30d': { hours: 24 * 30, granularity: 'hourly', fallbackRaw: false, rawLimit: 5000 },
};

/** Series affichees dans les graphiques historiques. */
export const CHART_METRICS = [
  { type: 'cpu', label: 'CPU', unit: '%', color: '#60a5fa', maxY: 100 },
  { type: 'ram', label: 'RAM', unit: '%', color: '#a78bfa', maxY: 100 },
  { type: 'disk_usage', label: 'Disque', unit: '%', color: '#fbbf24', maxY: 100 },
  { type: 'swap_usage', label: 'Swap', unit: '%', color: '#f87171', maxY: 100 },
  { type: 'network_in', label: 'Reseau entrant', unit: 'Mbit/s', color: '#34d399' },
  { type: 'network_out', label: 'Reseau sortant', unit: 'Mbit/s', color: '#22d3ee' },
  { type: 'temperature', label: 'Temperature', unit: '°C', color: '#fb923c' },
];

/**
 * Derniere valeur connue pour chaque type de metrique d'un device.
 */
export async function fetchLatestDeviceMetrics(deviceId) {
  const entries = await Promise.all(
    LATEST_TYPES.map(async (type) => {
      const { data } = await apiClient.get(`/devices/${deviceId}/metrics`, {
        params: { type, limit: 1, order: 'desc' },
      });
      const { items } = unwrapPaginated(data);
      return [type, items[0] ?? null];
    }),
  );

  return Object.fromEntries(entries);
}

function normalizeRaw(items) {
  return items
    .map((m) => ({
      at: m.recorded_at,
      value: Number(m.value),
    }))
    .filter((p) => !Number.isNaN(p.value))
    .sort((a, b) => new Date(a.at) - new Date(b.at));
}

function normalizeHourly(items) {
  return items
    .map((m) => ({
      at: m.hour_start,
      value: Number(m.avg_value),
    }))
    .filter((p) => !Number.isNaN(p.value))
    .sort((a, b) => new Date(a.at) - new Date(b.at));
}

/**
 * Historique d'une metrique pour graphiques (24h brut, 7j/30j horaire).
 */
export async function fetchDeviceMetricHistory(deviceId, type, range = '24h') {
  const cfg = RANGE_CONFIG[range] ?? RANGE_CONFIG['24h'];
  const to = new Date();
  const from = new Date(to.getTime() - cfg.hours * 3600 * 1000);
  const fromIso = from.toISOString();
  const toIso = to.toISOString();

  if (cfg.granularity === 'hourly') {
    const { data } = await apiClient.get(`/devices/${deviceId}/metrics`, {
      params: { type, from: fromIso, to: toIso, granularity: 'hourly' },
    });
    const { items } = unwrapPaginated(data);
    if (items.length > 0 || !cfg.fallbackRaw) {
      return { points: normalizeHourly(items), granularity: 'hourly' };
    }
  }

  const { data } = await apiClient.get(`/devices/${deviceId}/metrics`, {
    params: {
      type,
      from: fromIso,
      to: toIso,
      limit: cfg.rawLimit ?? cfg.limit ?? 1000,
      order: 'asc',
    },
  });
  const { items } = unwrapPaginated(data);

  return { points: normalizeRaw(items), granularity: 'raw' };
}
