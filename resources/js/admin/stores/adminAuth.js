import { defineStore } from 'pinia';
import request from '../utils/request';

export const useAdminAuthStore = defineStore('adminAuth', {
    state: () => ({
        user: null,
        initialized: false,
        loading: false,
    }),

    getters: {
        isAuthenticated: (state) => !!state.user,
        userName: (state) => state.user?.name || '',
    },

    actions: {
        async init() {
            try {
                const { data } = await request.get('/admin/api/me');
                this.user = data?.id ? data : null;
            } catch {
                this.user = null;
            } finally {
                this.initialized = true;
            }
        },

        async login(email, password, remember = false) {
            this.loading = true;
            try {
                const { data } = await request.post('/admin/login', { email, password, remember });
                this.user = data.user || data;
                return { success: true };
            } catch (e) {
                return { success: false, message: e.response?.data?.message || '登录失败' };
            } finally {
                this.loading = false;
            }
        },

        async logout() {
            try {
                await request.post('/admin/logout');
            } catch {
                // ignore
            }
            this.user = null;
        },
    },
});
