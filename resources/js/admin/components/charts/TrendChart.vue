<template>
  <div ref="chartRef" class="trend-chart-container"></div>
</template>

<script setup>
import { ref, onMounted, onBeforeUnmount, watch } from 'vue';
import * as echarts from 'echarts/core';
import { LineChart } from 'echarts/charts';
import { GridComponent, TooltipComponent, DataZoomComponent } from 'echarts/components';
import { CanvasRenderer } from 'echarts/renderers';
import { useAdminTheme } from '../../composables/useAdminTheme';

echarts.use([LineChart, GridComponent, TooltipComponent, DataZoomComponent, CanvasRenderer]);

const props = defineProps({
  data: { type: Array, default: () => [] },
});

const { isDark } = useAdminTheme();
const chartRef = ref(null);
let chart = null;

function getOption() {
  const textColor = isDark.value ? '#d1d5db' : '#6b7280';
  const lineColor = '#fc7c3c';
  const dates = props.data.map(d => {
    const date = new Date(d.date);
    return `${date.getMonth() + 1}/${date.getDate()}`;
  });
  const counts = props.data.map(d => d.count);

  return {
    tooltip: {
      trigger: 'axis',
      backgroundColor: isDark.value ? '#374151' : '#fff',
      borderColor: isDark.value ? '#4b5563' : '#e8e8e8',
      textStyle: { color: isDark.value ? '#e5e7eb' : '#1f2937', fontSize: 13 },
      formatter: (params) => {
        const p = params[0];
        const original = props.data[p.dataIndex];
        return `${original.date}<br/>点击量：<b>${p.value}</b>`;
      },
    },
    grid: { left: 40, right: 20, top: 20, bottom: 50 },
    xAxis: {
      type: 'category',
      data: dates,
      axisLine: { lineStyle: { color: isDark.value ? '#4b5563' : '#e8e8e8' } },
      axisLabel: { color: textColor, fontSize: 11 },
      axisTick: { show: false },
    },
    yAxis: {
      type: 'value',
      splitLine: { lineStyle: { color: isDark.value ? '#374151' : '#f0f0f0', type: 'dashed' } },
      axisLabel: { color: textColor, fontSize: 11 },
    },
    dataZoom: [{ type: 'inside' }, { type: 'slider', height: 20, bottom: 8, borderColor: 'transparent', backgroundColor: isDark.value ? '#374151' : '#f5f5f5', fillerColor: 'rgba(252,124,60,0.15)', handleStyle: { color: '#fc7c3c' } }],
    series: [{
      type: 'line',
      smooth: true,
      data: counts,
      symbol: 'circle',
      symbolSize: 6,
      itemStyle: { color: lineColor },
      lineStyle: { width: 3, color: lineColor },
      areaStyle: {
        color: new echarts.graphic.LinearGradient(0, 0, 0, 1, [
          { offset: 0, color: 'rgba(252,124,60,0.3)' },
          { offset: 1, color: 'rgba(252,124,60,0.02)' },
        ]),
      },
    }],
  };
}

function render() {
  if (!chart) return;
  chart.setOption(getOption(), true);
}

function resize() {
  chart && chart.resize();
}

onMounted(() => {
  chart = echarts.init(chartRef.value, null, { renderer: 'canvas' });
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
.trend-chart-container { width: 100%; height: 300px; }
</style>
