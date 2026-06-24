/**
 * Identite machine en mode navigateur (WebRTC + saisie manuelle IP).
 */

const PRIVATE_IP_RE = /^(192\.168\.|10\.|172\.(1[6-9]|2[0-9]|3[0-1])\.)/;

/**
 * @param {{ localIp?: string, hostname?: string }} [overrides]
 */
export async function collectBrowserIdentity(overrides = {}) {
  const hostname = resolveHostname(overrides.hostname);
  const manualIp = normalizeIp(overrides.localIp);
  const detected = manualIp ? null : await detectLocalIp();
  const ip = manualIp || detected;

  return {
    hostname,
    os: navigator.userAgent.includes('Windows') ? 'windows' : 'linux',
    os_version: navigator.userAgent,
    architecture: navigator.platform || 'unknown',
    ip_address: ip,
    mac_address: null,
  };
}

function resolveHostname(override) {
  const custom = override?.trim();
  if (custom) {
    localStorage.setItem('orion-agent-hostname', custom);
    return custom;
  }

  const stored = localStorage.getItem('orion-agent-hostname');
  if (stored) return stored;

  const name = `ORION-${crypto.randomUUID().slice(0, 8)}`;
  localStorage.setItem('orion-agent-hostname', name);
  return name;
}

function normalizeIp(value) {
  const ip = value?.trim();
  if (!ip || isLoopback(ip)) return null;
  return ip;
}

function isLoopback(ip) {
  return ip === '0.0.0.0' || ip.startsWith('127.');
}

function isPrivateIPv4(ip) {
  return PRIVATE_IP_RE.test(ip);
}

function extractIpv4(candidate) {
  const match = /([0-9]{1,3}(\.[0-9]{1,3}){3})/.exec(candidate);
  if (!match) return null;
  const ip = match[1];
  return isLoopback(ip) ? null : ip;
}

function pickBestIp(candidates) {
  const list = [...candidates];
  const privates = list.filter(isPrivateIPv4);
  if (privates.length) return privates[0];
  return list[0] || null;
}

/**
 * Detecte l'IP LAN via WebRTC (STUN). Retourne null si echec.
 */
export function detectLocalIp() {
  return new Promise((resolve) => {
    const candidates = new Set();
    let settled = false;

    const finish = () => {
      if (settled) return;
      settled = true;
      resolve(pickBestIp(candidates));
    };

    const timeout = setTimeout(finish, 8000);

    try {
      const pc = new RTCPeerConnection({
        iceServers: [{ urls: 'stun:stun.l.google.com:19302' }],
      });

      pc.createDataChannel('orion');
      pc.onicecandidate = (event) => {
        if (!event.candidate?.candidate) return;
        const ip = extractIpv4(event.candidate.candidate);
        if (ip) candidates.add(ip);
      };
      pc.onicegatheringstatechange = () => {
        if (pc.iceGatheringState === 'complete') {
          clearTimeout(timeout);
          pc.close();
          finish();
        }
      };

      pc.createOffer()
        .then((offer) => pc.setLocalDescription(offer))
        .catch(() => {
          clearTimeout(timeout);
          pc.close();
          finish();
        });
    } catch {
      clearTimeout(timeout);
      finish();
    }
  });
}
