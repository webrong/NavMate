<template>
  <div class="theme-switcher" :class="{ open: showOptions }">
    <button class="theme-toggle-btn" @click.stop="showOptions = !showOptions" title="切换主题">
      <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
        <circle cx="12" cy="12" r="5" />
        <line x1="12" y1="1" x2="12" y2="3" />
        <line x1="12" y1="21" x2="12" y2="23" />
        <line x1="4.22" y1="4.22" x2="5.64" y2="5.64" />
        <line x1="18.36" y1="18.36" x2="19.78" y2="19.78" />
        <line x1="1" y1="12" x2="3" y2="12" />
        <line x1="21" y1="12" x2="23" y2="12" />
        <line x1="4.22" y1="19.78" x2="5.64" y2="18.36" />
        <line x1="18.36" y1="5.64" x2="19.78" y2="4.22" />
      </svg>
    </button>
    <div v-if="showOptions" class="theme-options">
      <div
        v-for="t in themes" :key="t.id"
        class="theme-option" :class="{ active: currentTheme === t.id }"
        @click="setTheme(t.id); showOptions = false"
      >
        <div class="theme-preview" :style="{ background: t.gradient }"></div>
        <span>{{ t.label }}</span>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onUnmounted } from 'vue';
import { useTheme } from '../composables/useTheme';

const { currentTheme, setTheme, themes } = useTheme();
const showOptions = ref(false);

function onClickOutside(e) {
  if (!e.target.closest('.theme-switcher')) {
    showOptions.value = false;
  }
}

document.addEventListener('click', onClickOutside);
onUnmounted(() => document.removeEventListener('click', onClickOutside));
</script>
