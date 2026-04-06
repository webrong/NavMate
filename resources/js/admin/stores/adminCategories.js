import { defineStore } from 'pinia';
import request from '../utils/request';

export const useAdminCategoriesStore = defineStore('adminCategories', {
    state: () => ({
        items: [],
        loading: false,
    }),

    actions: {
        async fetchList(params = {}) {
            this.loading = true;
            try {
                const { data } = await request.get('/admin/api/categories', { params });
                this.items = data.data || [];
            } finally {
                this.loading = false;
            }
        },

        async create(formData) {
            const { data } = await request.post('/admin/api/categories', formData);
            return data;
        },

        async update(id, formData) {
            const { data } = await request.put(`/admin/api/categories/${id}`, formData);
            return data;
        },

        async remove(id) {
            const { data } = await request.delete(`/admin/api/categories/${id}`);
            return data;
        },
    },
});
