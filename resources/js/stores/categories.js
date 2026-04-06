import { defineStore } from 'pinia';
import request from '../utils/request';

export const useCategoryStore = defineStore('categories', {
    state: () => ({
        categories: [],
        loading: false,
        error: null,
        activeCategoryId: null,
    }),

    getters: {
        sidebarItems(state) {
            return state.categories.map(cat => ({
                id: cat.id,
                name: cat.name.replace(/[🔥💻🎮📌]/g, ''),
                icon: cat.icon || 'io-fuwutuijian',
                hasChildren: cat.children && cat.children.length > 0,
                children: (cat.children || []).map(child => ({
                    id: child.id,
                    name: child.name,
                })),
            }));
        },
    },

    actions: {
        async fetchCategories() {
            if (this.categories.length > 0) return;
            this.loading = true;
            this.error = null;
            try {
                const { data } = await request.get('/api/categories');
                this.categories = data;
            } catch (e) {
                this.error = e.message;
            } finally {
                this.loading = false;
            }
        },

        setActiveCategory(id) {
            this.activeCategoryId = id;
        },
    },
});
