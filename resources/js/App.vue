<template>
  <ThemeSwitcher />
  <TheHeader @toggle-mobile-menu="mobileMenuOpen = !mobileMenuOpen" />
  <SearchBar v-if="showSearch" />
  <div class="app-body">
    <TheSidebar
      :mobile-open="mobileMenuOpen"
      :collapsed="isCollapsed"
      @close-mobile="mobileMenuOpen = false"
      @toggle-collapse="isCollapsed = !isCollapsed"
    />
    <main class="main-content">
      <router-view />
    </main>
  </div>
</template>

<script setup>
import { ref, computed } from 'vue';
import { useRoute } from 'vue-router';
import TheHeader from './components/TheHeader.vue';
import TheSidebar from './components/TheSidebar.vue';
import ThemeSwitcher from './components/ThemeSwitcher.vue';
import SearchBar from './components/SearchBar.vue';
import { useCategoryStore } from './stores/categories';

const route = useRoute();
const mobileMenuOpen = ref(false);
const isCollapsed = ref(false);
const store = useCategoryStore();
store.fetchCategories();

const showSearch = computed(() => route.path === '/');
</script>
