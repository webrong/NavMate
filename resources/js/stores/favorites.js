import { defineStore } from 'pinia';
import request from '../utils/request';

export const useFavoritesStore = defineStore('favorites', {
    // Full records ({ site_id, site }) so the favorites page can render
    // straight from the store instead of keeping a second data source
    state: () => ({
        favorites: [],
        loaded: false,
    }),

    getters: {
        idSet(state) {
            return new Set(state.favorites.map((f) => f.site_id));
        },
        // Drops entries whose site was deleted on the admin side — the API
        // returns site: null for those
        sites(state) {
            return state.favorites.map((f) => f.site).filter(Boolean);
        },
    },

    actions: {
        async fetchFavorites() {
            try {
                const { data } = await request.get('/api/user/favorites');
                this.favorites = data;
            } catch {
                this.favorites = [];
            } finally {
                this.loaded = true;
            }
        },

        async toggleFavorite(site) {
            const siteId = typeof site === 'object' ? site.id : site;
            if (this.idSet.has(siteId)) {
                this.favorites = this.favorites.filter((f) => f.site_id !== siteId);
                try {
                    await request.delete(`/api/user/favorites/${siteId}`);
                } catch {
                    await this.fetchFavorites();
                }
            } else {
                // Optimistic add is only possible with the full site object
                if (typeof site === 'object') {
                    this.favorites.push({ site_id: site.id, site });
                }
                try {
                    await request.post('/api/user/favorites', { site_id: siteId });
                } catch {
                    await this.fetchFavorites();
                }
            }
        },

        clear() {
            this.favorites = [];
            this.loaded = false;
        },
    },
});
