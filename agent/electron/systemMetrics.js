import os from 'os';
import si from 'systeminformation';

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
  const [cpu, mem, fsSize] = await Promise.all([
    si.currentLoad(),
    si.mem(),
    si.fsSize(),
  ]);

  const disk = fsSize.find((d) => d.mount === '/' || d.mount === 'C:') || fsSize[0];
  const diskUsage = disk && disk.size > 0 ? (disk.used / disk.size) * 100 : 0;
  const recordedAt = new Date().toISOString();

  return [
    { type: 'cpu', value: round(cpu.currentLoad), recorded_at: recordedAt },
    { type: 'ram', value: round((1 - mem.available / mem.total) * 100), recorded_at: recordedAt },
    { type: 'disk_usage', value: round(diskUsage), recorded_at: recordedAt },
    { type: 'uptime', value: os.uptime(), recorded_at: recordedAt },
  ];
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
