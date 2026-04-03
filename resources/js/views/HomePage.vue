<template>
  <div class="home-page">
    <div class="content-area">
      <!-- Search results overlay -->
      <div v-if="hasSearch" class="content-section">
        <div class="section-header">
          <h4 class="section-title">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8" /><line x1="21" y1="21" x2="16.65" y2="16.65" /></svg>
            搜索结果
            <span class="search-result-count" v-if="!filtering">（{{ searchResults.length }} 个）</span>
          </h4>
          <button class="search-clear-btn" @click="clearSearch">清除搜索</button>
        </div>
        <div v-if="filtering" class="loading-state">搜索中...</div>
        <div v-else-if="searchResults.length === 0" class="section-empty">未找到相关结果</div>
        <div v-else class="site-grid">
          <SiteCard
            v-for="site in searchResults" :key="site.id"
            :site="site"
          />
        </div>
      </div>

      <!-- Normal categories -->
      <template v-else>
        <div v-if="loading" class="loading-state">加载中...</div>
        <div v-else-if="error" class="error-state">{{ error }}</div>
        <template v-else>
          <ContentSection
            v-for="cat in categories" :key="cat.id"
            :category="cat"
          />
        </template>
      </template>
    </div>
    <TheFooter />
  </div>
</template>

<script setup>
import { computed } from 'vue';
import { useCategoryStore } from '../stores/categories';
import { useSearchStore } from '../stores/search';
import ContentSection from '../components/ContentSection.vue';
import SiteCard from '../components/SiteCard.vue';
import TheFooter from '../components/TheFooter.vue';

const store = useCategoryStore();
const searchStore = useSearchStore();

const categories = computed(() => store.categories);
const loading = computed(() => store.loading);
const error = computed(() => store.error);
const searchResults = computed(() => searchStore.searchResults);
const filtering = computed(() => searchStore.filtering);
const hasSearch = computed(() => searchStore.searchResults.length > 0 || searchStore.filtering);

function clearSearch() {
  searchStore.searchResults = [];
  searchStore.keyword = '';
}
</script>
