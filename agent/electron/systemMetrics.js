import os from 'os';
import si from 'systeminformation';

/** Dernier echantillon reseau pour calculer le debit (octets/s). */
let lastNetworkSample = null;

/**
 * Collecte hostname, IP locale, MAC et metriques systeme (CPU, RAM, disque).
 */
export async function collectSystemIdentity() {
  const [network, system] = await Promise.all([
    si.networkInterfaces('default'),
    si.system(),
  ]);

  const iface = pickPrimaryInterface(network);
  const ip = iface?.ip4 || pickFallbackIp();
  const mac = iface?.mac || null;

  return {
    hostname: os.hostname(),
    os: process.platform === 'win32' ? 'windows' : 'linux',
    os_version: os.release(),
    architecture: process.arch,
    ip_address: ip,
    mac_address: mac && mac !== '00:00:00:00:00:00' ? mac : null,
  };
}

export async function collectMetrics() {
  const [cpu, mem, fsSize, networkRates, temperature] = await Promise.all([
    si.currentLoad(),
    si.mem(),
    si.fsSize(),
    collectNetworkRates(),
    collectTemperature(),
  ]);

  const disk = fsSize.find((d) => d.mount === '/' || d.mount === 'C:') || fsSize[0];
  const diskUsage = disk && disk.size > 0 ? (disk.used / disk.size) * 100 : 0;
  const diskTotalGb = disk && disk.size > 0 ? round(disk.size / 1e9) : 0;
  const ramTotalGb = mem.total > 0 ? round(mem.total / 1e9) : 0;
  const swapUsage = mem.swaptotal > 0 ? round((mem.swapused / mem.swaptotal) * 100) : 0;
  const recordedAt = new Date().toISOString();

  const points = [
    { type: 'cpu', value: round(cpu.currentLoad), recorded_at: recordedAt },
    { type: 'ram', value: round((1 - mem.available / mem.total) * 100), recorded_at: recordedAt },
    { type: 'ram_total', value: ramTotalGb, recorded_at: recordedAt },
    { type: 'swap_usage', value: swapUsage, recorded_at: recordedAt },
    { type: 'disk', value: diskTotalGb, recorded_at: recordedAt },
    { type: 'disk_usage', value: round(diskUsage), recorded_at: recordedAt },
    { type: 'network_in', value: networkRates.inMbps, recorded_at: recordedAt },
    { type: 'network_out', value: networkRates.outMbps, recorded_at: recordedAt },
    { type: 'uptime', value: os.uptime(), recorded_at: recordedAt },
  ];

  if (temperature != null) {
    points.push({ type: 'temperature', value: temperature, recorded_at: recordedAt });
  }

  return points;
}

/**
 * Debit entrant/sortant en Mbit/s (delta octets entre deux collectes).
 */
async function collectNetworkRates() {
  const stats = await si.networkStats();
  const primary = pickPrimaryNetworkStat(stats);

  if (!primary) {
    return { inMbps: 0, outMbps: 0 };
  }

  const now = Date.now();
  let inMbps = 0;
  let outMbps = 0;

  if (
    lastNetworkSample
    && lastNetworkSample.iface === primary.iface
    && primary.rx_bytes >= lastNetworkSample.rx_bytes
    && primary.tx_bytes >= lastNetworkSample.tx_bytes
  ) {
    const dt = (now - lastNetworkSample.at) / 1000;
    if (dt > 0) {
      const rxBps = (primary.rx_bytes - lastNetworkSample.rx_bytes) / dt;
      const txBps = (primary.tx_bytes - lastNetworkSample.tx_bytes) / dt;
      inMbps = round((rxBps * 8) / 1e6);
      outMbps = round((txBps * 8) / 1e6);
    }
  }

  lastNetworkSample = {
    iface: primary.iface,
    rx_bytes: primary.rx_bytes,
    tx_bytes: primary.tx_bytes,
    at: now,
  };

  return { inMbps, outMbps };
}

async function collectTemperature() {
  try {
    const data = await si.cpuTemperature();
    const raw = data.main ?? data.max ?? data.chipset ?? data.cores?.[0];
    if (raw == null || raw <= 0) {
      return null;
    }
    return round(raw);
  } catch {
    return null;
  }
}

function pickPrimaryNetworkStat(stats) {
  if (!Array.isArray(stats) || stats.length === 0) {
    return null;
  }

  const active = stats.filter((s) => s.operstate === 'up' || s.operstate === 'unknown');
  const pool = active.length > 0 ? active : stats;

  return pool.reduce((best, current) => {
    const total = (current.rx_bytes || 0) + (current.tx_bytes || 0);
    const bestTotal = (best?.rx_bytes || 0) + (best?.tx_bytes || 0);
    return total > bestTotal ? current : best;
  }, pool[0]);
}

function pickPrimaryInterface(interfaces) {
  if (!interfaces) return null;
  const list = Array.isArray(interfaces) ? interfaces : [interfaces];
  return list.find((i) => i.default && i.ip4 && !i.internal) || list.find((i) => i.ip4 && !i.internal);
}

function pickFallbackIp() {
  const nets = os.networkInterfaces();
  for (const name of Object.keys(nets)) {
    for (const net of nets[name] || []) {
      if (net.family === 'IPv4' && !net.internal) {
        return net.address;
      }
    }
  }
  return '127.0.0.1';
}

function round(n) {
  return Math.round(n * 100) / 100;
}
