import { defineStore } from 'pinia';
import request from '../utils/request';

export const useCategoryStore = defineStore('categories', {
    state: () => ({
        categories: [],
        loading: false,
        error: null,
        activeCategoryId: null,
        // Timestamp of last successful fetch. Used to auto-invalidate stale
        // cache so changes made in the admin panel eventually show up on the
        // front-end without requiring a hard refresh.
        lastFetchedAt: 0,
    }),

    // Cache TTL in ms (5 min). After this, fetchCategories re-queries.
    // Can be bypassed with forceFetch().
    _cacheTtl: 5 * 60 * 1000,

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
        async fetchCategories(force = false) {
            // Skip if we have fresh data and the caller didn't force a refresh.
            if (!force && this.categories.length > 0 && Date.now() - this.lastFetchedAt < 5 * 60 * 1000) {
                return;
            }
            this.loading = true;
            this.error = null;
            try {
                const { data } = await request.get('/api/categories');
                this.categories = data;
                this.lastFetchedAt = Date.now();
            } catch (e) {
                this.error = e.message;
            } finally {
                this.loading = false;
            }
        },

        /** Force-refresh categories, ignoring the cache TTL. Call this after
         *  admin operations that change category data. */
        invalidate() {
            this.lastFetchedAt = 0;
        },

        setActiveCategory(id) {
            this.activeCategoryId = id;
        },
    },
});
