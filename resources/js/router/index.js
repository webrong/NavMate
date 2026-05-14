import { createRouter, createWebHistory } from 'vue-router';
import { useAuthStore } from '../stores/auth';

const routes = [
  { path: '/', name: 'home', component: () => import('../views/HomePage.vue'), meta: { title: null } },
  { path: '/about', name: 'about', component: () => import('../views/AboutPage.vue'), meta: { title: '关于我们' } },
  { path: '/terms', name: 'terms', component: () => import('../views/TermsPage.vue'), meta: { title: '使用条款' } },
  { path: '/favorites', name: 'favorites', component: () => import('../views/FavoritesPage.vue'), meta: { title: '我的收藏', auth: true } },
  { path: '/settings', name: 'settings', component: () => import('../views/SettingsPage.vue'), meta: { title: '偏好设置', auth: true } },
  { path: '/profile', name: 'profile', component: () => import('../views/ProfilePage.vue'), meta: { title: '个人资料', auth: true } },
  { path: '/:pathMatch(.*)*', name: 'not-found', component: () => import('../views/NotFoundPage.vue'), meta: { title: '页面未找到' } },
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

router.beforeEach(async (to) => {
  if (to.meta.auth) {
    const authStore = useAuthStore();
    if (!authStore.initialized) {
      await authStore.init();
    }
    if (!authStore.isAuthenticated) {
      return { path: '/', query: { login: 'true' } };
    }
  }
});

export default router;
