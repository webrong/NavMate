<template>
  <div ref="chartRef" class="category-chart-container"></div>
</template>

<script setup>
import { ref, onMounted, onBeforeUnmount, watch } from 'vue';
import * as echarts from 'echarts/core';
import { BarChart } from 'echarts/charts';
import { GridComponent, TooltipComponent } from 'echarts/components';
import { CanvasRenderer } from 'echarts/renderers';
import { useAdminTheme } from '../../composables/useAdminTheme';

echarts.use([BarChart, GridComponent, TooltipComponent, CanvasRenderer]);

const props = defineProps({
  data: { type: Array, default: () => [] },
});

const { isDark } = useAdminTheme();
const chartRef = ref(null);
let chart = null;

function getOption() {
  const textColor = isDark.value ? '#d1d5db' : '#6b7280';
  // Reverse so the highest item shows at the top of a horizontal bar chart.
  const sorted = [...props.data].sort((a, b) => b.clicks - a.clicks);
  const names = sorted.map(d => `${d.icon || ''} ${d.name}`);
  const clicks = sorted.map(d => d.clicks);

  return {
    tooltip: {
      trigger: 'axis',
      axisPointer: { type: 'shadow' },
      backgroundColor: isDark.value ? '#374151' : '#fff',
      borderColor: isDark.value ? '#4b5563' : '#e8e8e8',
      textStyle: { color: isDark.value ? '#e5e7eb' : '#1f2937', fontSize: 13 },
      formatter: (params) => {
        const p = params[0];
        return `${p.name}<br/>点击量：<b>${p.value}</b>`;
      },
    },
    grid: { left: 10, right: 40, top: 10, bottom: 10, containLabel: true },
    xAxis: {
      type: 'value',
      splitLine: { lineStyle: { color: isDark.value ? '#374151' : '#f0f0f0', type: 'dashed' } },
      axisLabel: { color: textColor, fontSize: 11 },
    },
    yAxis: {
      type: 'category',
      data: names,
      axisLine: { lineStyle: { color: isDark.value ? '#4b5563' : '#e8e8e8' } },
      axisLabel: { color: textColor, fontSize: 12 },
      axisTick: { show: false },
    },
    series: [{
      type: 'bar',
      data: clicks,
      barWidth: '50%',
      label: { show: true, position: 'right', color: textColor, fontSize: 12 },
      itemStyle: {
        borderRadius: [0, 4, 4, 0],
        color: new echarts.graphic.LinearGradient(0, 0, 1, 0, [
          { offset: 0, color: 'rgba(252,124,60,0.7)' },
          { offset: 1, color: '#fc7c3c' },
        ]),
      },
    }],
  };
}

function render() { chart && chart.setOption(getOption(), true); }
function resize() { chart && chart.resize(); }

onMounted(() => {
  chart = echarts.init(chartRef.value);
  render();
  window.addEventListener('resize', resize);
});
onBeforeUnmount(() => {
  window.removeEventListener('resize', resize);
  chart && chart.dispose();
});
watch(() => props.data, render, { deep: true });
watch(isDark, render);
</script>

<style scoped>
.category-chart-container { width: 100%; height: 320px; }
</style>
