<template>
  <div style="height:260px">
    <div v-if="title" class="mb-3 text-sm font-semibold text-slate-900">{{ title }}</div>
    <Line :data="chartData" :options="options" />
  </div>
</template>

<script setup>
import { Line } from 'vue-chartjs';
import { Chart, LineElement, PointElement, CategoryScale, LinearScale, Title, Tooltip, Legend, Filler } from 'chart.js';
import { computed } from 'vue';

Chart.register(LineElement, PointElement, CategoryScale, LinearScale, Title, Tooltip, Legend, Filler);

const props = defineProps({
  timeseries: { type: Array, default: () => [] },
  title: { type: String, default: '' },
  seriesLabel: { type: String, default: 'Closed cases' },
});

const labels = computed(() => props.timeseries.map((t) => t.label));
const values = computed(() => props.timeseries.map((t) => Number(t.count || 0)));

const chartData = computed(() => ({
  labels: labels.value,
  datasets: [
    {
      label: props.seriesLabel,
      data: values.value,
      borderColor: '#0f172a',
      backgroundColor: 'rgba(15,23,42,0.08)',
      tension: 0.3,
      fill: true,
      pointRadius: 3,
      pointBackgroundColor: '#0f172a',
    },
  ],
}));

const options = {
  responsive: true,
  maintainAspectRatio: false,
  plugins: {
    legend: { display: false },
    tooltip: { mode: 'index', intersect: false },
  },
  scales: {
    x: { grid: { display: false } },
    y: { beginAtZero: true, ticks: { precision: 0 } },
  },
};
</script>
