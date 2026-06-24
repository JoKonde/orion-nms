const STORAGE_KEY = 'orion-agent-config';

export function createConfigStore() {
  return {
    get(key, fallback = {}) {
      try {
        const raw = localStorage.getItem(STORAGE_KEY);
        const data = raw ? JSON.parse(raw) : {};
        return data[key] ?? fallback;
      } catch {
        return fallback;
      }
    },
    set(key, value) {
      try {
        const raw = localStorage.getItem(STORAGE_KEY);
        const data = raw ? JSON.parse(raw) : {};
        data[key] = value;
        localStorage.setItem(STORAGE_KEY, JSON.stringify(data));
      } catch {
        // ignore quota errors
      }
    },
  };
}
