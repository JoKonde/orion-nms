import { app, BrowserWindow, ipcMain, Tray, Menu, nativeImage } from 'electron';
import path from 'path';
import { fileURLToPath } from 'url';
import Store from 'electron-store';
import { AgentRunner } from './agentRunner.js';

const __dirname = path.dirname(fileURLToPath(import.meta.url));
const isDev = !app.isPackaged;

const store = new Store({ name: 'orion-agent-config' });
let mainWindow = null;
let tray = null;
let runner = null;

function createWindow() {
  mainWindow = new BrowserWindow({
    width: 520,
    height: 640,
    show: true,
    autoHideMenuBar: true,
    webPreferences: {
      preload: path.join(__dirname, 'preload.js'),
      contextIsolation: true,
      nodeIntegration: false,
    },
  });

  if (isDev) {
    mainWindow.loadURL('http://127.0.0.1:5174');
  } else {
    mainWindow.loadFile(path.join(__dirname, '../dist/index.html'));
  }

  mainWindow.on('close', (e) => {
    if (!app.isQuitting) {
      e.preventDefault();
      mainWindow?.hide();
    }
  });
}

function createTray() {
  const icon = nativeImage.createEmpty();
  tray = new Tray(icon);
  tray.setToolTip('ORION Agent');
  tray.on('double-click', () => mainWindow?.show());
  refreshTrayMenu();
}

function refreshTrayMenu() {
  const status = runner?.getStatus();
  const menu = Menu.buildFromTemplate([
    {
      label: status?.running ? 'Agent actif' : 'Agent arrete',
      enabled: false,
    },
    { type: 'separator' },
    {
      label: 'Ouvrir ORION Agent',
      click: () => {
        mainWindow?.show();
        mainWindow?.focus();
      },
    },
    {
      label: status?.running ? 'Arreter' : 'Demarrer',
      click: () => {
        if (status?.running) runner.stop();
        else runner.start();
        refreshTrayMenu();
      },
    },
    { type: 'separator' },
    {
      label: 'Quitter',
      click: () => {
        app.isQuitting = true;
        app.quit();
      },
    },
  ]);
  tray?.setContextMenu(menu);
}

function broadcastStatus(status) {
  mainWindow?.webContents.send('agent:status', status);
  refreshTrayMenu();
}

function setupIpc() {
  ipcMain.handle('agent:getStatus', () => runner.getStatus());

  ipcMain.handle('agent:saveConfig', (_e, partial) => runner.saveConfig(partial));

  ipcMain.handle('agent:register', async () => {
    const result = await runner.register();
    runner.start();
    return { ok: true, agent: result.agent };
  });

  ipcMain.handle('agent:start', () => {
    runner.start();
    return runner.getStatus();
  });

  ipcMain.handle('agent:stop', () => {
    runner.stop();
    return runner.getStatus();
  });
}

app.whenReady().then(() => {
  runner = new AgentRunner(store, broadcastStatus);
  setupIpc();
  createWindow();
  createTray();

  const config = store.get('config', {});
  if (config.apiKey && config.agentUuid) {
    runner.start();
  }
});

app.on('before-quit', () => {
  app.isQuitting = true;
  runner?.stop();
});

app.on('window-all-closed', (e) => {
  e.preventDefault();
});
