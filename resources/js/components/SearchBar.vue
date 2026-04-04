<template>
  <div class="search-banner">
    <div class="banner-orb banner-orb-1"></div>
    <div class="banner-orb banner-orb-2"></div>
    <div class="banner-orb banner-orb-3"></div>
    <div class="search-container">
      <!-- Inner search box (centered, constrained width) -->
      <div class="big-search">
        <!-- Category tabs -->
        <div class="search-group-tabs">
          <button
            v-for="g in groups" :key="g.id"
            class="search-group-tab"
            :class="{ active: activeGroup === g.id }"
            @click="setGroup(g.id)"
          >{{ g.label }}</button>
        </div>

        <!-- Search input -->
        <form class="search-form" @submit.prevent="doSearch">
          <input
            v-model="keyword"
            type="text"
            class="search-input"
            :placeholder="placeholder"
            aria-label="搜索关键词"
            autocomplete="off"
            @keydown.enter="doSearch"
          />
          <button type="submit" class="search-btn" aria-label="搜索">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <circle cx="11" cy="11" r="8" />
              <line x1="21" y1="21" x2="16.65" y2="16.65" />
            </svg>
          </button>
        </form>

        <!-- Engine tabs -->
        <div class="search-engine-tabs">
          <button
            v-for="e in currentEngines" :key="e.id"
            class="search-engine-tab"
            :class="{ active: activeEngine === e.id }"
            @click="setEngine(e.id)"
          >{{ e.label }}</button>
        </div>
      </div>

      <!-- Bulletin (inside search-container, outside big-search) -->
      <div class="bulletin-bar">
        <span class="bulletin-icon">&#128227;</span>
        <span class="bulletin-text">{{ siteName }} 欢迎使用，速按 CTRL+D 收藏本站</span>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed, inject } from 'vue';
import { useSearchStore } from '../stores/search';

const searchStore = useSearchStore();
const siteName = inject('siteName', '导航');

const keyword = computed({
  get: () => searchStore.keyword,
  set: (v) => { searchStore.keyword = v; },
});
const activeGroup = computed(() => searchStore.activeGroup);
const activeEngine = computed(() => searchStore.activeEngine);
const groups = computed(() => searchStore.groups);
const currentEngines = computed(() => searchStore.currentEngines);
const placeholder = computed(() => searchStore.placeholder);

function setGroup(id) { searchStore.setGroup(id); }
function setEngine(id) { searchStore.setEngine(id); }
function doSearch() { searchStore.doSearch(); }
</script>
