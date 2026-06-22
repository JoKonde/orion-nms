import { contextBridge, ipcRenderer } from 'electron';

contextBridge.exposeInMainWorld('orionAgent', {
  getStatus: () => ipcRenderer.invoke('agent:getStatus'),
  saveConfig: (partial) => ipcRenderer.invoke('agent:saveConfig', partial),
  register: () => ipcRenderer.invoke('agent:register'),
  start: () => ipcRenderer.invoke('agent:start'),
  stop: () => ipcRenderer.invoke('agent:stop'),
  onStatus: (callback) => {
    const listener = (_event, status) => callback(status);
    ipcRenderer.on('agent:status', listener);
    return () => ipcRenderer.removeListener('agent:status', listener);
  },
});
