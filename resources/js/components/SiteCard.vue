<template>
  <a :href="site.url" target="_blank" rel="noopener noreferrer" class="site-card" @click="trackClick">
    <button
      v-if="authStore.isAuthenticated"
      class="site-fav"
      :class="{ active: favoritesStore.isFavorite(site.id) }"
      @click.stop.prevent="handleFav"
      :title="favoritesStore.isFavorite(site.id) ? '取消收藏' : '收藏'"
    >
      <svg width="14" height="14" viewBox="0 0 24 24" :fill="favoritesStore.isFavorite(site.id) ? 'currentColor' : 'none'" stroke="currentColor" stroke-width="2"><path d="M12 21.35l-1.45-1.32C5.4 15.36 2 12.28 2 8.5 2 5.42 4.42 3 7.5 3c1.74 0 3.41.81 4.5 2.09C13.09 3.81 14.76 3 16.5 3 19.58 3 22 5.42 22 8.5c0 3.78-3.4 6.86-8.55 11.54L12 21.35z"/></svg>
    </button>
    <div v-if="site.favicon_url && !imgError" class="site-card-bg">
      <img :src="site.favicon_url" alt="" />
    </div>
    <div class="site-favicon">
      <img v-if="site.favicon_url && !imgError" :src="site.favicon_url" :alt="site.title" loading="lazy" @error="imgError = true" />
      <span v-else class="site-favicon-letter">{{ firstLetter }}</span>
    </div>
    <div class="site-info">
      <span class="site-name">{{ site.title }}</span>
      <span class="site-desc" v-if="site.description">{{ site.description }}</span>
    </div>
    <span class="site-goto">&#8599;</span>
  </a>
</template>

<script setup>
import { ref, computed } from 'vue';
import { useAuthStore } from '../stores/auth';
import { useFavoritesStore } from '../stores/favorites';

const props = defineProps({
  site: { type: Object, required: true },
});
const imgError = ref(false);
const authStore = useAuthStore();
const favoritesStore = useFavoritesStore();

const firstLetter = computed(() => {
  return props.site.title ? props.site.title.charAt(0).toUpperCase() : '?';
});

function handleFav() {
  favoritesStore.toggleFavorite(props.site.id);
}

function trackClick() {
  if (!props.site.id) return;
  const payload = JSON.stringify({ site_id: props.site.id });
  const url = '/api/click';
  if (navigator.sendBeacon) {
    navigator.sendBeacon(url, new Blob([payload], { type: 'application/json' }));
  } else {
    const token = document.querySelector('meta[name="csrf-token"]')?.content;
    fetch(url, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': token,
        'Accept': 'application/json',
      },
      body: payload,
    }).catch(() => {});
  }
}
</script>
