<template>
  <a href="#main-content" class="skip-link">跳到主要内容</a>
  <ThemeSwitcher />
  <TheHeader @toggle-mobile-menu="mobileMenuOpen = !mobileMenuOpen" />
  <!-- Announcement bar -->
  <div v-if="siteSettings.hasAnnouncement" class="announcement-bar" v-html="sanitizedAnnouncement"></div>
  <SearchBar v-if="showSearch" />
  <div class="app-body">
    <TheSidebar
      :mobile-open="mobileMenuOpen"
      :collapsed="isCollapsed"
      @close-mobile="mobileMenuOpen = false"
      @toggle-collapse="isCollapsed = !isCollapsed"
    />
    <main id="main-content" class="main-content">
      <router-view />
    </main>
  </div>
  <TheFooter />
  <AuthModals />
  <ToastNotifications />
</template>

<script setup>
import { ref, computed, provide, watch } from 'vue';
import { useRoute } from 'vue-router';
import { sanitizeHtml } from './composables/useSanitize';
import { updateTitle, setBaseSiteName, setRobots } from './composables/useSeo';
import TheHeader from './components/TheHeader.vue';
import TheSidebar from './components/TheSidebar.vue';
import ThemeSwitcher from './components/ThemeSwitcher.vue';
import SearchBar from './components/SearchBar.vue';
import TheFooter from './components/TheFooter.vue';
import AuthModals from './components/auth/AuthModals.vue';
import ToastNotifications from './components/ToastNotifications.vue';
import { useCategoryStore } from './stores/categories';
import { useAuthStore } from './stores/auth';
import { useFavoritesStore } from './stores/favorites';
import { useLayoutStore } from './stores/layout';
import { useUserLinksStore } from './stores/userLinks';
import { useSiteSettingsStore } from './stores/siteSettings';

const route = useRoute();
const mobileMenuOpen = ref(false);
const isCollapsed = ref(false);
const store = useCategoryStore();
const authStore = useAuthStore();
const favoritesStore = useFavoritesStore();
const layoutStore = useLayoutStore();
const userLinksStore = useUserLinksStore();
const siteSettings = useSiteSettingsStore();
Promise.all([
  store.fetchCategories(),
  authStore.init(),
  siteSettings.fetchSettings(),
]);

const showSearch = computed(() => route.path === '/');

const sanitizedAnnouncement = computed(() => sanitizeHtml(siteSettings.settings.announcement));
provide('siteName', computed(() => siteSettings.siteName));

watch(() => authStore.isAuthenticated, (authed) => {
  if (authed) {
    Promise.all([
      favoritesStore.fetchFavorites(),
      layoutStore.fetchLayout(),
      userLinksStore.fetchLinks(),
    ]);
  } else {
    favoritesStore.clear();
    layoutStore.clear();
    userLinksStore.clear();
  }
});

// SEO: Update page title on route change
watch(() => route.meta.title, (title) => {
  updateTitle(title);
}, { immediate: true });

// SEO: Update base site name when settings load
watch(() => siteSettings.siteName, (name) => {
  if (name) {
    setBaseSiteName(name);
    if (!route.meta.title) {
      updateTitle(null);
    }
  }
});
</script>

<style scoped>
.announcement-bar {
  background: var(--announcement-bg);
  border-bottom: 1px solid var(--announcement-border);
  padding: 8px 16px;
  text-align: center;
  font-size: 13px;
  color: var(--announcement-color);
  line-height: 1.6;
}
</style>
