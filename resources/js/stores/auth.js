import { defineStore } from 'pinia';
import request from '../utils/request';

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
        avatarUrl: (state) => state.user?.avatar || '',
    },

    actions: {
        async init() {
            try {
                const { data } = await request.get('/api/user');
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
                const { data } = await request.post('/api/login', { email, password, remember });
                this.user = data;
                return { success: true };
            } catch (e) {
                const response = e.response;
                const message = response?.data?.message || '登录失败';
                const unverified = response?.data?.unverified === true;
                const unverifiedEmail = response?.data?.email || '';
                return { success: false, message, unverified, unverifiedEmail };
            } finally {
                this.loading = false;
            }
        },

        async register(name, email, password, passwordConfirmation) {
            this.loading = true;
            try {
                const { data } = await request.post('/api/register', {
                    name,
                    email,
                    password,
                    password_confirmation: passwordConfirmation,
                });
                return { success: true, message: data.message, email: data.email };
            } catch (e) {
                const message = e.response?.data?.message || Object.values(e.response?.data?.errors || {}).flat().join(' ') || '注册失败';
                return { success: false, message };
            } finally {
                this.loading = false;
            }
        },

        async logout() {
            try {
                await request.post('/api/logout');
            } catch {
                // ignore
            }
            this.user = null;
        },

        async forgotPassword(email) {
            try {
                const { data } = await request.post('/api/forgot-password', { email });
                return { success: true, message: data.message };
            } catch (e) {
                const message = e.response?.data?.message || '发送失败，请稍后再试';
                return { success: false, message };
            }
        },

        async resetPassword(email, password, passwordConfirmation, token) {
            this.loading = true;
            try {
                const { data } = await request.post('/api/reset-password', {
                    email,
                    password,
                    password_confirmation: passwordConfirmation,
                    token,
                });
                return { success: true, message: data.message };
            } catch (e) {
                const message = e.response?.data?.message || '重置失败，请重试';
                return { success: false, message };
            } finally {
                this.loading = false;
            }
        },

        async resendVerification(email) {
            try {
                const { data } = await request.post('/api/resend-verification', { email });
                return { success: true, message: data.message };
            } catch (e) {
                const message = e.response?.data?.message || '发送失败，请稍后再试';
                return { success: false, message };
            }
        },

        async updateProfile(updates) {
            this.loading = true;
            try {
                const { data } = await request.put('/api/user/profile', updates);
                this.user = data;
                return { success: true };
            } catch (e) {
                const message = e.response?.data?.message || '更新失败';
                return { success: false, message };
            } finally {
                this.loading = false;
            }
        },

        async uploadAvatar(file) {
            const form = new FormData();
            form.append('avatar', file);
            try {
                const { data } = await request.post('/api/user/avatar', form, {
                    headers: { 'Content-Type': 'multipart/form-data' },
                });
                this.user = { ...this.user, avatar: data.avatar };
                return { success: true, avatar: data.avatar };
            } catch (e) {
                const message = e.response?.data?.message || Object.values(e.response?.data?.errors || {}).flat().join(' ') || '上传失败';
                return { success: false, message };
            }
        },
    },
});
