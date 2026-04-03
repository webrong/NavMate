<template>
  <a :href="site.url" target="_blank" class="site-card" @click="trackClick">
    <div class="site-favicon">
      <img v-if="site.favicon_url" :src="site.favicon_url" :alt="site.title" loading="lazy" />
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
import { computed } from 'vue';

const props = defineProps({
  site: { type: Object, required: true },
});

const firstLetter = computed(() => {
  return props.site.title ? props.site.title.charAt(0).toUpperCase() : '?';
});

function trackClick() {
  if (!props.site.id) return;
  const token = document.querySelector('meta[name="csrf-token"]')?.content;
  const payload = JSON.stringify({ site_id: props.site.id });
  const url = '/api/click';
  if (navigator.sendBeacon) {
    navigator.sendBeacon(url, new Blob([payload], { type: 'application/json' }));
  } else {
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
