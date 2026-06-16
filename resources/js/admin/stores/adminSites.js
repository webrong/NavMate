import { defineStore } from 'pinia';
import request from '../utils/request';

export const useAdminSitesStore = defineStore('adminSites', {
    state: () => ({
        items: [],
        total: 0,
        categories: [],
        loading: false,
        currentFilters: { keyword: '', category_id: undefined, is_public: undefined, page: 1, limit: 15 },
    }),

    actions: {
        async fetchList(params = {}) {
            this.loading = true;
            try {
                const query = { ...this.currentFilters, ...params };
                this.currentFilters = query;
                const { data } = await request.get('/admin/api/sites', { params: query });
                this.items = data.data || [];
                this.total = data.total || 0;
            } catch {
                // Error toast already shown by request.js interceptor.
                // Swallow to prevent unhandled rejection in the view.
            } finally {
                this.loading = false;
            }
        },

        async fetchCategories() {
            try {
                const res = await request.get('/admin/api/sites/categories');
                this.categories = res.data?.data || [];
            } catch (e) {
                console.error('Failed to fetch categories:', e);
            }
        },

        async create(formData) {
            const { data } = await request.post('/admin/api/sites', formData);
            return data;
        },

        async update(id, formData) {
            const { data } = await request.put(`/admin/api/sites/${id}`, formData);
            return data;
        },

        async remove(id) {
            const { data } = await request.delete(`/admin/api/sites/${id}`);
            return data;
        },

        async fetchUrl(url) {
            const { data } = await request.post('/admin/api/sites/fetch-url', { url });
            return data;
        },
    },
});
