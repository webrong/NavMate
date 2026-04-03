<template>
  <div class="home-page">
    <div class="content-area">
      <div v-if="loading" class="loading-state">加载中...</div>
      <div v-else-if="error" class="error-state">{{ error }}</div>
      <template v-else>
        <ContentSection
          v-for="cat in categories" :key="cat.id"
          :category="cat"
        />
      </template>
    </div>
    <TheFooter />
  </div>
</template>

<script setup>
import { computed } from 'vue';
import { useCategoryStore } from '../stores/categories';
import ContentSection from '../components/ContentSection.vue';
import TheFooter from '../components/TheFooter.vue';

const store = useCategoryStore();
const categories = computed(() => store.categories);
const loading = computed(() => store.loading);
const error = computed(() => store.error);
</script>
