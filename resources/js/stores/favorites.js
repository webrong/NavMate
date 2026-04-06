import { defineStore } from 'pinia';
import request from '../utils/request';

export const useFavoritesStore = defineStore('favorites', {
    state: () => ({
        ids: [],
        loaded: false,
    }),

    getters: {
        idSet(state) {
            return new Set(state.ids);
        },
        isFavorite: (state) => {
            const set = new Set(state.ids);
            return (siteId) => set.has(siteId);
        },
    },

    actions: {
        async fetchFavorites() {
            try {
                const { data } = await request.get('/api/user/favorites');
                this.ids = data.map((f) => f.site_id);
            } catch {
                this.ids = [];
            } finally {
                this.loaded = true;
            }
        },

        async toggleFavorite(siteId) {
            if (this.idSet.has(siteId)) {
                this.ids = this.ids.filter((id) => id !== siteId);
                try {
                    await request.delete(`/api/user/favorites/${siteId}`);
                } catch {
                    this.ids.push(siteId);
                }
            } else {
                this.ids.push(siteId);
                try {
                    await request.post('/api/user/favorites', { site_id: siteId });
                } catch {
                    this.ids = this.ids.filter((id) => id !== siteId);
                }
            }
        },

        clear() {
            this.ids = [];
            this.loaded = false;
        },
    },
});
