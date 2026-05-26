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
        <template v-if="loading">
          <div v-for="n in 3" :key="'sk-'+n" class="skeleton-section">
            <div class="skeleton-header">
              <div class="skeleton-header-line"></div>
            </div>
            <div class="skeleton-grid">
              <div v-for="i in 8" :key="'c-'+i" class="skeleton-card">
                <div class="skeleton-avatar"></div>
                <div class="skeleton-text-group">
                  <div class="skeleton-text skeleton-text-name"></div>
                  <div class="skeleton-text skeleton-text-desc"></div>
                </div>
                <div class="skeleton-arrow"></div>
              </div>
            </div>
          </div>
        </template>
        <div v-else-if="error" class="error-state">{{ error }}</div>
        <template v-else>
          <UserQuickLinks />
          <template v-for="(cat, idx) in categories" :key="cat.id">
            <ContentSection :category="cat" />
            <AdBanner
              v-if="shouldShowAd(idx)"
              :ad="getContentAd(idx)"
            />
          </template>
        </template>
      </template>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue';
import { useCategoryStore } from '../stores/categories';
import { useLayoutStore } from '../stores/layout';
import { useSearchStore } from '../stores/search';
import { useAdsStore } from '../stores/ads';
import ContentSection from '../components/ContentSection.vue';
import SiteCard from '../components/SiteCard.vue';
import UserQuickLinks from '../components/UserQuickLinks.vue';
import AdBanner from '../components/AdBanner.vue';

const store = useCategoryStore();
const layoutStore = useLayoutStore();
const searchStore = useSearchStore();
const adsStore = useAdsStore();

const AD_INTERVAL = 3;

function shouldShowAd(idx) {
  return (idx + 1) % AD_INTERVAL === 0
    && idx < categories.value.length - 1
    && adsStore.contentBetween.length > 0;
}

function getContentAd(idx) {
  const ads = adsStore.contentBetween;
  if (!ads.length) return null;
  return ads[(Math.floor((idx + 1) / AD_INTERVAL) - 1) % ads.length];
}

const categories = computed(() => {
  const all = store.categories;
  if (!layoutStore.loaded || layoutStore.data.length === 0) return all;

  const layoutMap = {};
  layoutStore.data.forEach(item => {
    layoutMap[item.category_id] = item;
  });

  // Filter visible and apply user sort_order
  const result = all
    .filter(cat => {
      const layout = layoutMap[cat.id];
      return !layout || layout.visible !== false;
    })
    .map(cat => {
      const layout = layoutMap[cat.id];
      return { ...cat, _sort: layout ? layout.sort_order : 999 };
    })
    .sort((a, b) => a._sort - b._sort);

  return result;
});
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
