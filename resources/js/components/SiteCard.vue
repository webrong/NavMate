<template>
  <a :href="site.url" target="_blank" class="site-card" @click="trackClick">
    <div class="site-favicon">
      <img v-if="site.favicon_url" :src="site.favicon_url" :alt="site.title" loading="lazy" />
      <span v-else class="site-favicon-letter">{{ firstLetter }}</span>
    </div>
    <span class="site-name">{{ site.title }}</span>
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
  fetch('/api/click', {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json',
      'X-CSRF-TOKEN': token,
      'Accept': 'application/json',
    },
    body: JSON.stringify({ site_id: props.site.id }),
  }).catch(() => {});
}
</script>
