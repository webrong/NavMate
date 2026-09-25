<template>
  <div class="content-area">
    <div class="content-section">
      <div class="section-header">
        <h2 class="section-title">我的收藏</h2>
        <span class="search-result-count">{{ sites.length }} 个站点</span>
      </div>
      <div v-if="loading" class="loading-state">加载中...</div>
      <div v-else-if="sites.length === 0" class="section-empty">还没有收藏任何站点</div>
      <div v-else class="site-grid">
        <SiteCard v-for="site in sites" :key="site.id" :site="site" />
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed, onMounted } from 'vue';
import { useFavoritesStore } from '../stores/favorites';
import SiteCard from '../components/SiteCard.vue';

// Consume the favorites store directly — App.vue already fetched the list
// on login, and toggling a favorite here now updates this page immediately
const favoritesStore = useFavoritesStore();
const sites = computed(() => favoritesStore.sites);
const loading = computed(() => !favoritesStore.loaded);

onMounted(() => {
  if (!favoritesStore.loaded) {
    favoritesStore.fetchFavorites();
  }
});
</script>
