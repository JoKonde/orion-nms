/**
 * Metriques disponibles dans le navigateur (approximation RAM navigateur).
 */
export async function collectBrowserMetrics() {
  const recordedAt = new Date().toISOString();
  const mem = performance.memory;
  let ram = 0;

  if (mem && mem.jsHeapSizeLimit > 0) {
    ram = round((mem.usedJSHeapSize / mem.jsHeapSizeLimit) * 100);
  }

  return [
    { type: 'cpu', value: 0, recorded_at: recordedAt },
    { type: 'ram', value: ram, recorded_at: recordedAt },
    { type: 'disk_usage', value: 0, recorded_at: recordedAt },
    { type: 'uptime', value: round(performance.now() / 1000), recorded_at: recordedAt },
  ];
}

function round(n) {
  return Math.round(n * 100) / 100;
}
