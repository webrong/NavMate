<template>
  <header class="main-header">
    <nav class="header-nav">
      <div class="header-inner">
        <a href="/" class="logo-link">
          <img :src="'/static/image/logo.png'" alt="导航" class="logo-img" />
        </a>
        <div class="header-links d-none d-md-flex">
          <a href="/" class="header-link active">
            <span>首页</span>
          </a>
        </div>
        <div class="flex-fill"></div>

        <!-- Quick Tools -->
        <div class="quick-tools d-none d-md-flex">
          <div
            v-for="group in toolGroups" :key="group.label"
            class="tool-group"
            @mouseenter="openTool(group.label)"
            @mouseleave="scheduleClose"
          >
            <span class="tool-trigger" :class="{ active: activeTool === group.label }">
              {{ group.label }}
              <svg class="tool-arrow" viewBox="0 0 12 12" width="10" height="10"><path d="M2 4l4 4 4-4" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/></svg>
            </span>
            <Transition name="dropdown">
              <div v-if="activeTool === group.label" class="tool-dropdown">
                <a
                  v-for="item in group.items" :key="item.name"
                  :href="item.url"
                  target="_blank"
                  rel="noopener"
                  class="tool-dropdown-item"
                >{{ item.name }}</a>
              </div>
            </Transition>
          </div>
        </div>

        <div class="header-actions">
          <button class="header-search-btn d-none d-md-flex" @click="scrollToSearch" title="搜索">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <circle cx="11" cy="11" r="8" />
              <line x1="21" y1="21" x2="16.65" y2="16.65" />
            </svg>
          </button>
          <button class="mobile-menu-btn d-md-none" @click="$emit('toggle-mobile-menu')" aria-label="菜单">
            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <line x1="3" y1="6" x2="21" y2="6" />
              <line x1="3" y1="12" x2="21" y2="12" />
              <line x1="3" y1="18" x2="21" y2="18" />
            </svg>
          </button>
        </div>
      </div>
    </nav>
  </header>
</template>

<script setup>
import { ref } from 'vue';
import { toolGroups } from '../config/tools';

defineEmits(['toggle-mobile-menu']);

const activeTool = ref(null);
let closeTimer = null;

function openTool(label) {
  clearTimeout(closeTimer);
  activeTool.value = label;
}

function scheduleClose() {
  closeTimer = setTimeout(() => {
    activeTool.value = null;
  }, 150);
}

function scrollToSearch() {
  const el = document.querySelector('.search-input');
  if (el) {
    el.scrollIntoView({ behavior: 'smooth', block: 'center' });
    el.focus();
  }
}
</script>

<style scoped>
.header-search-btn {
  width: 34px;
  height: 34px;
  border-radius: 50%;
  background: none;
  border: none;
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  color: var(--muted-color2);
  transition: all 0.2s;
}

.header-search-btn:hover {
  background: rgba(0,0,0,0.04);
  color: var(--theme-color);
}

.quick-tools {
  display: flex;
  align-items: center;
  gap: 2px;
}

.tool-group {
  position: relative;
}

.tool-trigger {
  display: flex;
  align-items: center;
  gap: 2px;
  padding: 6px 8px;
  font-size: 13px;
  border-radius: 6px;
  cursor: pointer;
  color: var(--muted-color2);
  transition: all 0.2s;
  white-space: nowrap;
}

.tool-trigger:hover,
.tool-trigger.active {
  background: rgba(0,0,0,0.04);
  color: var(--main-color);
}

.tool-arrow {
  transition: transform 0.2s;
}

.tool-trigger.active .tool-arrow {
  transform: rotate(180deg);
}

.tool-dropdown {
  position: absolute;
  top: 100%;
  left: 50%;
  transform: translateX(-50%);
  min-width: 120px;
  background: var(--main-bg-color);
  border-radius: 10px;
  box-shadow: 0 8px 24px var(--main-shadow);
  border: 1px solid rgba(0,0,0,0.06);
  padding: 6px;
  z-index: 1001;
}

.tool-dropdown-item {
  display: block;
  padding: 7px 12px;
  font-size: 13px;
  color: var(--main-color);
  border-radius: 6px;
  transition: background 0.15s;
  white-space: nowrap;
}

.tool-dropdown-item:hover {
  background: rgba(0,0,0,0.04);
  color: var(--theme-color);
}

.dropdown-enter-active,
.dropdown-leave-active {
  transition: opacity 0.15s, transform 0.15s;
}

.dropdown-enter-from,
.dropdown-leave-to {
  opacity: 0;
  transform: translateX(-50%) translateY(-4px);
}
</style>
