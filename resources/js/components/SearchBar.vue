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
            :class="{ 'has-suggestions': showSuggestions && suggestions.length }"
            :placeholder="placeholder"
            aria-label="搜索关键词"
            autocomplete="off"
            @input="onInput"
            @focus="showSuggestions = suggestions.length > 0"
          />
          <button type="submit" class="search-btn" aria-label="搜索">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <circle cx="11" cy="11" r="8" />
              <line x1="21" y1="21" x2="16.65" y2="16.65" />
            </svg>
          </button>
        </form>

        <!-- Suggestions dropdown -->
        <Transition name="dropdown">
          <div v-if="showSuggestions && suggestions.length > 0" class="search-suggestions">
            <div
              v-for="item in suggestions" :key="item.id"
              class="search-suggestion-item"
              @mousedown.prevent="selectSuggestion(item)"
            >
              <img v-if="item.favicon_url" :src="item.favicon_url" class="search-suggestion-icon" alt="" />
              <span v-else class="search-suggestion-letter">{{ item.title.charAt(0) }}</span>
              <span class="search-suggestion-text">{{ item.title }}</span>
            </div>
          </div>
        </Transition>

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
        <span class="bulletin-icon">&#128197;</span>
        <span class="bulletin-text">{{ dateTimeStr }} ｜ {{ lunarStr }}</span>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed, inject, ref, onMounted, onUnmounted } from 'vue';
import { useSearchStore } from '../stores/search';
import { useCategoryStore } from '../stores/categories';
import { formatLunar } from '../utils/lunar';

const searchStore = useSearchStore();
const categoryStore = useCategoryStore();
const siteName = inject('siteName', '导航');

// Suggestions
const suggestions = ref([]);
const showSuggestions = ref(false);
let debounceTimer = null;

// Clock — time updates every second, date/lunar cached by day
const timeStr = ref('');
const WEEK = ['日', '一', '二', '三', '四', '五', '六'];
const pad = (n) => String(n).padStart(2, '0');
let timer = null;

// Cache lunar by date string to avoid recalculating every second
const cachedDateStr = ref('');
const cachedLunar = ref('');
const cachedDatePart = ref('');

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

const dateTimeStr = computed(() => cachedDatePart.value + ' ' + timeStr.value);
const lunarStr = computed(() => cachedLunar.value);

onMounted(() => {
  updateClock();
  timer = setInterval(updateClock, 1000);
  document.addEventListener('click', onClickOutside);
});

onUnmounted(() => {
  clearInterval(timer);
  clearTimeout(debounceTimer);
  document.removeEventListener('click', onClickOutside);
});

const keyword = computed({
  get: () => searchStore.keyword,
  set: (v) => { searchStore.keyword = v; },
});
const activeGroup = computed(() => searchStore.activeGroup);
const activeEngine = computed(() => searchStore.activeEngine);
const groups = computed(() => searchStore.groups);
const currentEngines = computed(() => searchStore.currentEngines);
const placeholder = computed(() => searchStore.placeholder);

function setGroup(id) {
  searchStore.setGroup(id);
  showSuggestions.value = false;
  suggestions.value = [];
}
function setEngine(id) {
  searchStore.setEngine(id);
  showSuggestions.value = false;
  suggestions.value = [];
}
function doSearch() {
  showSuggestions.value = false;
  suggestions.value = [];
  searchStore.doSearch();
}

function onInput() {
  const val = searchStore.keyword.trim();
  const engine = searchStore.currentEngine;

  if (engine?.type !== 'site' || !val) {
    suggestions.value = [];
    showSuggestions.value = false;
    return;
  }

  clearTimeout(debounceTimer);
  debounceTimer = setTimeout(() => {
    const q = val.toLowerCase();
    const allSites = [];
    categoryStore.categories.forEach(cat => {
      (cat.children || []).forEach(child => {
        (child.sites || []).forEach(s => {
          allSites.push({ title: s.title, favicon_url: s.favicon_url, id: s.id });
        });
      });
      (cat.sites || []).forEach(s => {
        allSites.push({ title: s.title, favicon_url: s.favicon_url, id: s.id });
      });
    });
    suggestions.value = allSites.filter(s => s.title.toLowerCase().includes(q)).slice(0, 6);
    showSuggestions.value = suggestions.value.length > 0;
  }, 300);
}

function selectSuggestion(item) {
  searchStore.keyword = item.title;
  showSuggestions.value = false;
  suggestions.value = [];
  searchStore.doSearch();
}

function onClickOutside(e) {
  if (!e.target.closest('.search-form')) {
    showSuggestions.value = false;
  }
}
</script>
