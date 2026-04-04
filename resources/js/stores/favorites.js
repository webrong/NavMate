import { defineStore } from 'pinia';
import axios from 'axios';

export const useFavoritesStore = defineStore('favorites', {
    state: () => ({
        ids: [],
        loaded: false,
    }),

    getters: {
        isFavorite: (state) => (siteId) => state.ids.includes(siteId),
    },

    actions: {
        async fetchFavorites() {
            try {
                const { data } = await axios.get('/api/user/favorites');
                this.ids = data.map((f) => f.site_id);
            } catch {
                this.ids = [];
            } finally {
                this.loaded = true;
            }
        },

        async toggleFavorite(siteId) {
            if (this.ids.includes(siteId)) {
                this.ids = this.ids.filter((id) => id !== siteId);
                try {
                    await axios.delete(`/api/user/favorites/${siteId}`);
                } catch {
                    this.ids.push(siteId);
                }
            } else {
                this.ids.push(siteId);
                try {
                    await axios.post('/api/user/favorites', { site_id: siteId });
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
