<template>
  <span class="bulletin-text">{{ display }}</span>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue';
import { formatLunar } from '../utils/lunar';

// Clock — time updates every second, date/lunar cached by day
const timeStr = ref('');
const WEEK = ['日', '一', '二', '三', '四', '五', '六'];
const pad = (n) => String(n).padStart(2, '0');
let timer = null;

// Cache lunar by date string to avoid recalculating every second
const cachedDateStr = ref('');
const cachedDatePart = ref('');
const cachedLunar = ref('');

function updateClock() {
  const d = new Date();
  timeStr.value = `${pad(d.getHours())}:${pad(d.getMinutes())}:${pad(d.getSeconds())}`;

  const dateKey = `${d.getFullYear()}-${d.getMonth()}-${d.getDate()}`;
  if (dateKey !== cachedDateStr.value) {
    cachedDateStr.value = dateKey;
    cachedDatePart.value = `${d.getFullYear()}年${pad(d.getMonth() + 1)}月${pad(d.getDate())}日 星期${WEEK[d.getDay()]}`;
    cachedLunar.value = formatLunar(d);
  }
}

// Lunar part can be empty (date out of the supported range) —
// degrade to solar-only display in that case.
const display = computed(() => {
  const solar = cachedDatePart.value + ' ' + timeStr.value;
  return cachedLunar.value ? `${solar} ｜ ${cachedLunar.value}` : solar;
});

onMounted(() => {
  updateClock();
  timer = setInterval(updateClock, 1000);
});

onUnmounted(() => {
  clearInterval(timer);
});
</script>
