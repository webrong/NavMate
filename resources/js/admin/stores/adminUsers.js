import { defineStore } from 'pinia';
import request from '../utils/request';

export const useAdminUsersStore = defineStore('adminUsers', {
    state: () => ({
        items: [],
        total: 0,
        loading: false,
        currentFilters: { keyword: '', page: 1, limit: 15 },
    }),

    actions: {
        async fetchList(params = {}) {
            this.loading = true;
            try {
                const query = { ...this.currentFilters, ...params };
                this.currentFilters = query;
                const { data } = await request.get('/admin/api/users', { params: query });
                this.items = data.data || [];
                this.total = data.total || 0;
            } catch {
                // Error toast already shown by request.js interceptor.
                // Swallow to prevent unhandled rejection in the view.
            } finally {
                this.loading = false;
            }
        },

        async update(id, formData) {
            const { data } = await request.put(`/admin/api/users/${id}`, formData);
            return data;
        },

        async remove(id) {
            const { data } = await request.delete(`/admin/api/users/${id}`);
            return data;
        },
    },
});
