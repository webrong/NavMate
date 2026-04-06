<template>
  <div v-if="password" class="password-strength">
    <div class="strength-bar">
      <div class="strength-fill" :style="{ width: percent + '%', background: color }"></div>
    </div>
    <span class="strength-text" :style="{ color }">{{ label }}</span>
  </div>
</template>

<script setup>
import { computed } from 'vue';

const props = defineProps({
  password: { type: String, default: '' },
});

const score = computed(() => {
  const p = props.password;
  if (!p) return 0;
  let s = 0;
  if (p.length >= 8) s++;
  if (p.length >= 12) s++;
  if (/[a-z]/.test(p) && /[A-Z]/.test(p)) s++;
  if (/\d/.test(p)) s++;
  if (/[^a-zA-Z0-9]/.test(p)) s++;
  return s;
});

const percent = computed(() => Math.min(score.value * 20, 100));
const label = computed(() => ['', '很弱', '弱', '一般', '强', '很强'][score.value] || '');
const color = computed(() => ['', '#ef4444', '#f97316', '#eab308', '#22c55e', '#16a34a'][score.value] || '');
</script>

<style scoped>
.password-strength {
  display: flex;
  align-items: center;
  gap: 8px;
  margin-top: 4px;
  margin-bottom: 8px;
}

.strength-bar {
  flex: 1;
  height: 3px;
  background: rgba(0, 0, 0, 0.08);
  border-radius: 2px;
  overflow: hidden;
}

.strength-fill {
  height: 100%;
  border-radius: 2px;
  transition: width 0.3s, background 0.3s;
}

.strength-text {
  font-size: 11px;
  font-weight: 500;
  white-space: nowrap;
}
</style>
