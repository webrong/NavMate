<template>
  <div :id="'section-' + category.id" class="content-section">
    <div class="section-header">
      <h4 class="section-title">{{ cleanName }}</h4>
      <ul v-if="tabs.length > 1" class="section-tabs">
        <li
          v-for="(tab, idx) in tabs" :key="tab.id"
          class="section-tab"
          :class="{ active: activeTabIdx === idx }"
          @click="activeTabIdx = idx"
        >{{ tab.name }}</li>
      </ul>
      <span class="section-more" v-if="tabs.length > 1">more+</span>
    </div>
    <div class="site-grid">
      <SiteCard
        v-for="site in activeSites" :key="site.id"
        :site="site"
      />
    </div>
  </div>
</template>

<script setup>
import { ref, computed } from 'vue';
import SiteCard from './SiteCard.vue';

const props = defineProps({
  category: { type: Object, required: true },
});

const activeTabIdx = ref(0);

const cleanName = computed(() => props.category.name.replace(/[🔥💻🎮📌]/g, ''));

const tabs = computed(() => {
  if (props.category.children && props.category.children.length > 0) {
    return props.category.children;
  }
  return props.category.sites && props.category.sites.length > 0
    ? [{ id: props.category.id, name: props.category.name, sites: props.category.sites }]
    : [];
});

const activeSites = computed(() => {
  const tab = tabs.value[activeTabIdx.value];
  return tab ? (tab.sites || []) : [];
});
</script>
