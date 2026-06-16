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
            // /admin/api/me is polled on every admin page load to detect an
            // existing session. When there's no session the server returns 401,
            // which axios surfaces as a red "GET /me 401" line in the console —
            // purely cosmetic, but noisy. Swallow that specific 401 by resolving
            // to null from the interceptor; the request layer above never sees
            // a rejection so nothing gets logged as an error.
            try {
                const { data } = await request.get('/admin/api/me', {
                    _silent401: true,
                });
                this.user = data?.id ? data : null;
            } catch {
                this.user = null;
            } finally {
                this.initialized = true;
            }
        },

        async login(username, password, remember = false) {
            this.loading = true;
            try {
                const { data } = await request.post('/admin/login', { username, password, remember });
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
