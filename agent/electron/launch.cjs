/**
 * Lance Electron apres que Vite soit pret (Windows-friendly).
 */
const http = require('http');
const path = require('path');
const { spawn } = require('child_process');

const VITE_HOSTS = ['127.0.0.1', 'localhost'];
const VITE_PORT = 5174;
const ROOT = path.join(__dirname, '..');
const MAX_ATTEMPTS = 90;

function probeVite(host) {
  return new Promise((resolve, reject) => {
    const req = http.get(`http://${host}:${VITE_PORT}`, (res) => {
      res.resume();
      if (res.statusCode && res.statusCode >= 200 && res.statusCode < 500) {
        resolve(host);
        return;
      }
      reject(new Error(`HTTP ${res.statusCode}`));
    });

    req.setTimeout(2000, () => {
      req.destroy();
      reject(new Error('timeout'));
    });
    req.on('error', reject);
  });
}

async function waitForVite(attempt = 0) {
  if (attempt >= MAX_ATTEMPTS) {
    throw new Error(`Vite non disponible sur le port ${VITE_PORT}.`);
  }

  for (const host of VITE_HOSTS) {
    try {
      return await probeVite(host);
    } catch {
      // essai suivant
    }
  }

  await new Promise((r) => setTimeout(r, 500));
  return waitForVite(attempt + 1);
}

function launchElectron(viteHost) {
  const electronPath = require('electron');
  const child = spawn(electronPath, ['.'], {
    cwd: ROOT,
    stdio: 'inherit',
    env: {
      ...process.env,
      ORION_VITE_URL: `http://${viteHost}:${VITE_PORT}`,
    },
  });

  child.on('exit', (code) => process.exit(code ?? 0));
  child.on('error', (err) => {
    console.error('Echec lancement Electron:', err.message);
    process.exit(1);
  });
}

waitForVite()
  .then((host) => {
    console.log(`Vite pret sur http://${host}:${VITE_PORT} — lancement Electron...`);
    launchElectron(host);
  })
  .catch((err) => {
    console.error(err.message);
    process.exit(1);
  });
