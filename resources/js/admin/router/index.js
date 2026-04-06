import { createRouter, createWebHistory } from 'vue-router';
import { useAdminAuthStore } from '../stores/adminAuth';

const routes = [
    {
        path: '/admin/login',
        name: 'Login',
        component: () => import('../views/LoginView.vue'),
        meta: { guest: true },
    },
    {
        path: '/admin',
        component: () => import('../components/AdminLayout.vue'),
        meta: { auth: true },
        children: [
            {
                path: 'dashboard',
                name: 'Dashboard',
                component: () => import('../views/DashboardView.vue'),
                meta: { title: '仪表盘' },
            },
            {
                path: 'categories',
                name: 'Categories',
                component: () => import('../views/CategoriesView.vue'),
                meta: { title: '分类管理' },
            },
            {
                path: 'sites',
                name: 'Sites',
                component: () => import('../views/SitesView.vue'),
                meta: { title: '站点管理' },
            },
            {
                path: 'users',
                name: 'Users',
                component: () => import('../views/UsersView.vue'),
                meta: { title: '用户管理' },
            },
            {
                path: 'analytics',
                name: 'Analytics',
                component: () => import('../views/AnalyticsView.vue'),
                meta: { title: '数据统计' },
            },
            {
                path: 'bookmarks',
                name: 'Bookmarks',
                component: () => import('../views/BookmarksView.vue'),
                meta: { title: '书签导入' },
            },
            {
                path: 'settings',
                name: 'Settings',
                component: () => import('../views/SettingsView.vue'),
                meta: { title: '系统设置' },
            },
            {
                path: 'system/monitor',
                name: 'SystemMonitor',
                component: () => import('../views/SystemMonitorView.vue'),
                meta: { title: '系统监控' },
            },
            {
                path: 'system/upgrade',
                name: 'SystemUpgrade',
                component: () => import('../views/SystemUpgradeView.vue'),
                meta: { title: '系统升级' },
            },
        ],
    },
    {
        path: '/admin/:pathMatch(.*)*',
        redirect: '/admin/dashboard',
    },
];

const router = createRouter({
    history: createWebHistory(),
    routes,
});

router.beforeEach(async (to) => {
    const authStore = useAdminAuthStore();

    if (!authStore.initialized) {
        await authStore.init();
    }

    if (to.meta.auth && !authStore.isAuthenticated) {
        return { name: 'Login' };
    }

    if (to.meta.guest && authStore.isAuthenticated) {
        return { name: 'Dashboard' };
    }
});

export default router;
