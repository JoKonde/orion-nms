import {
  Chart as ChartJS,
  CategoryScale,
  LinearScale,
  PointElement,
  LineElement,
  Tooltip,
  Filler,
} from 'chart.js';
import { Line } from 'react-chartjs-2';

ChartJS.register(CategoryScale, LinearScale, PointElement, LineElement, Tooltip, Filler);

function formatAxisTime(iso, range) {
  if (!iso) return '';
  const d = new Date(iso);
  if (range === '24h') {
    return d.toLocaleTimeString('fr-FR', { hour: '2-digit', minute: '2-digit' });
  }
  return d.toLocaleDateString('fr-FR', { day: '2-digit', month: 'short', hour: '2-digit', minute: '2-digit' });
}

export function MetricLineChart({ label, unit, color, points, maxY, range }) {
  if (!points?.length) {
    return <p className="metric-chart__empty">Aucune donnee sur cette periode.</p>;
  }

  const labels = points.map((p) => formatAxisTime(p.at, range));

  const chartData = {
    labels,
    datasets: [
      {
        label: `${label} (${unit})`,
        data: points.map((p) => p.value),
        borderColor: color,
        backgroundColor: `${color}33`,
        fill: true,
        tension: 0.25,
        pointRadius: points.length > 60 ? 0 : 2,
        borderWidth: 2,
      },
    ],
  };

  const options = {
    responsive: true,
    maintainAspectRatio: false,
    interaction: { mode: 'index', intersect: false },
    plugins: {
      legend: { display: false },
      tooltip: {
        callbacks: {
          label: (ctx) => `${ctx.parsed.y?.toFixed(2) ?? '—'} ${unit}`,
        },
      },
    },
    scales: {
      x: {
        ticks: { maxTicksLimit: 6, color: '#94a3b8', font: { size: 10 } },
        grid: { color: 'rgba(148, 163, 184, 0.12)' },
      },
      y: {
        min: 0,
        ...(maxY ? { max: maxY } : {}),
        ticks: { color: '#94a3b8', font: { size: 10 } },
        grid: { color: 'rgba(148, 163, 184, 0.12)' },
      },
    },
  };

  return (
    <div className="metric-chart">
      <Line data={chartData} options={options} />
    </div>
  );
}
