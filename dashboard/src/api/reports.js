import { apiClient } from './client';

export async function fetchReportTypes() {
  const { data } = await apiClient.get('/reports/types');
  return data.types ?? [];
}

export async function fetchReportPreview({ type, from, to }) {
  const { data } = await apiClient.get('/reports/preview', {
    params: { type, from, to },
  });
  return data.report;
}

function parseFilename(disposition, fallback) {
  const match = disposition?.match(/filename="?([^"]+)"?/);
  return match?.[1] ?? fallback;
}

/** Telecharge un rapport CSV ou ouvre le HTML imprimable. */
export async function downloadReport({ type, format, from, to }) {
  const response = await apiClient.get('/reports/export', {
    params: { type, format, from, to },
    responseType: 'blob',
  });

  const blob = new Blob([response.data], {
    type: response.headers['content-type'] || 'application/octet-stream',
  });
  const url = window.URL.createObjectURL(blob);

  if (format === 'html') {
    window.open(url, '_blank', 'noopener,noreferrer');
  } else {
    const link = document.createElement('a');
    link.href = url;
    link.download = parseFilename(response.headers['content-disposition'], `orion-${type}.csv`);
    link.click();
  }

  window.setTimeout(() => window.URL.revokeObjectURL(url), 60_000);
}
