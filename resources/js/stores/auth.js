import { defineStore } from 'pinia';
import axios from 'axios';

export const useAuthStore = defineStore('auth', {
    state: () => ({
        user: null,
        initialized: false,
        loading: false,
    }),

    getters: {
        isAuthenticated: (state) => !!state.user,
        isAdmin: (state) => state.user?.is_admin === true,
        userName: (state) => state.user?.name || '',
    },

    actions: {
        async init() {
            try {
                const { data } = await axios.get('/api/user');
                this.user = data && data.id ? data : null;
            } catch {
                this.user = null;
            } finally {
                this.initialized = true;
            }
        },

        async login(email, password, remember = false) {
            this.loading = true;
            try {
                const { data } = await axios.post('/api/login', { email, password, remember });
                this.user = data;
                return { success: true };
            } catch (e) {
                const message = e.response?.data?.message || '登录失败';
                return { success: false, message };
            } finally {
                this.loading = false;
            }
        },

        async register(name, email, password, passwordConfirmation) {
            this.loading = true;
            try {
                const { data } = await axios.post('/api/register', {
                    name,
                    email,
                    password,
                    password_confirmation: passwordConfirmation,
                });
                this.user = data;
                return { success: true };
            } catch (e) {
                const message = e.response?.data?.message || Object.values(e.response?.data?.errors || {}).flat().join(' ') || '注册失败';
                return { success: false, message };
            } finally {
                this.loading = false;
            }
        },

        async logout() {
            try {
                await axios.post('/api/logout');
            } catch {
                // ignore
            }
            this.user = null;
        },
    },
});
