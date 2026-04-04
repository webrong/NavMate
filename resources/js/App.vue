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
  <TheFooter />
  <AuthModals />
</template>

<script setup>
import { ref, computed, provide, watch } from 'vue';
import { useRoute } from 'vue-router';
import TheHeader from './components/TheHeader.vue';
import TheSidebar from './components/TheSidebar.vue';
import ThemeSwitcher from './components/ThemeSwitcher.vue';
import SearchBar from './components/SearchBar.vue';
import TheFooter from './components/TheFooter.vue';
import AuthModals from './components/auth/AuthModals.vue';
import { useCategoryStore } from './stores/categories';
import { useAuthStore } from './stores/auth';
import { useFavoritesStore } from './stores/favorites';
import { useLayoutStore } from './stores/layout';

const route = useRoute();
const mobileMenuOpen = ref(false);
const isCollapsed = ref(false);
const store = useCategoryStore();
const authStore = useAuthStore();
const favoritesStore = useFavoritesStore();
const layoutStore = useLayoutStore();
store.fetchCategories();
authStore.init();

const showSearch = computed(() => route.path === '/');

const siteName = typeof document !== 'undefined' ? document.title : '导航';
provide('siteName', siteName);

watch(() => authStore.isAuthenticated, (authed) => {
  if (authed) {
    favoritesStore.fetchFavorites();
    layoutStore.fetchLayout();
  } else {
    favoritesStore.clear();
    layoutStore.clear();
  }
});
</script>
