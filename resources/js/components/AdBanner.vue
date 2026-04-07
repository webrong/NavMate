<template>
  <div v-if="ad" class="ad-banner" :class="'ad-' + ad.position">
    <a :href="ad.link_url" :target="ad.target || '_blank'" rel="noopener noreferrer sponsored" class="ad-link">
      <img :src="ad.image_url" :alt="ad.title" class="ad-image" loading="lazy" />
    </a>
    <div class="ad-label">广告</div>
  </div>
</template>

<script setup>
defineProps({
  ad: { type: Object, required: true },
});
</script>

<style scoped>
.ad-banner {
  position: relative;
  margin: 12px 0;
  border-radius: var(--main-radius);
  overflow: hidden;
}

.ad-link {
  display: block;
  text-decoration: none;
}

.ad-image {
  width: 100%;
  display: block;
  border-radius: var(--main-radius);
  object-fit: cover;
}

.ad-footer_above .ad-image {
  max-height: 80px;
}

.ad-label {
  position: absolute;
  bottom: 4px;
  right: 6px;
  font-size: 10px;
  color: var(--muted-color);
  opacity: 0.6;
  pointer-events: none;
}

.ad-sidebar_bottom .ad-image {
  border-radius: 6px;
}
</style>
