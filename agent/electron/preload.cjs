const { contextBridge, ipcRenderer } = require('electron');

contextBridge.exposeInMainWorld('orionNative', {
  isElectron: true,
  collectIdentity: () => ipcRenderer.invoke('native:identity'),
  collectMetrics: () => ipcRenderer.invoke('native:metrics'),
});
