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
import { ref, onMounted } from 'vue';
import axios from 'axios';
import SiteCard from '../components/SiteCard.vue';

const sites = ref([]);
const loading = ref(true);

onMounted(async () => {
  try {
    const { data } = await axios.get('/api/user/favorites');
    sites.value = data.map((f) => f.site || f);
  } catch {
    // ignore
  } finally {
    loading.value = false;
  }
});
</script>
