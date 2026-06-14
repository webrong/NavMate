<template>
  <div ref="chartRef" class="hourly-chart-container"></div>
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
  const hours = props.data.map(d => `${d.hour}:00`);
  const counts = props.data.map(d => d.count);

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
    grid: { left: 40, right: 20, top: 20, bottom: 30 },
    xAxis: {
      type: 'category',
      data: hours,
      axisLine: { lineStyle: { color: isDark.value ? '#4b5563' : '#e8e8e8' } },
      axisLabel: { color: textColor, fontSize: 11, interval: 3 },
      axisTick: { show: false },
    },
    yAxis: {
      type: 'value',
      splitLine: { lineStyle: { color: isDark.value ? '#374151' : '#f0f0f0', type: 'dashed' } },
      axisLabel: { color: textColor, fontSize: 11 },
    },
    series: [{
      type: 'bar',
      data: counts,
      barWidth: '60%',
      itemStyle: {
        borderRadius: [4, 4, 0, 0],
        color: new echarts.graphic.LinearGradient(0, 0, 0, 1, [
          { offset: 0, color: '#fc7c3c' },
          { offset: 1, color: 'rgba(252,124,60,0.6)' },
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
.hourly-chart-container { width: 100%; height: 300px; }
</style>
