import { createRouter, createWebHistory } from 'vue-router';
import { useAuthStore } from '../stores/auth';

const routes = [
  { path: '/', name: 'home', component: () => import('../views/HomePage.vue') },
  { path: '/favorites', name: 'favorites', component: () => import('../views/FavoritesPage.vue'), meta: { auth: true } },
  { path: '/settings', name: 'settings', component: () => import('../views/SettingsPage.vue'), meta: { auth: true } },
];

const router = createRouter({
  history: createWebHistory(),
  routes,
  scrollBehavior(to) {
    if (to.hash) {
      return { el: to.hash, behavior: 'smooth' };
    }
    return { top: 0 };
  },
});

router.beforeEach((to) => {
  if (to.meta.auth) {
    const authStore = useAuthStore();
    if (!authStore.isAuthenticated) {
      return { path: '/', query: { login: 'true' } };
    }
  }
});

export default router;
