import { defineStore } from 'pinia';
import request from '../utils/request';

export const useAdsStore = defineStore('ads', {
    state: () => ({
        ads: [],
        loaded: false,
        // Auto-invalidate stale ads so admin changes eventually show up.
        lastFetchedAt: 0,
    }),

    getters: {
        contentBetween: (state) => state.ads.filter(ad => ad.position === 'content_between'),
        sidebarBottom: (state) => state.ads.filter(ad => ad.position === 'sidebar_bottom'),
        footerAbove: (state) => state.ads.filter(ad => ad.position === 'footer_above'),
    },

    actions: {
        async fetchAds(force = false) {
            // Skip if we have fresh data (5 min TTL) and the caller didn't
            // force a refresh.
            if (!force && this.loaded && Date.now() - this.lastFetchedAt < 5 * 60 * 1000) {
                return;
            }
            try {
                const { data } = await request.get('/api/ads');
                this.ads = data;
                this.loaded = true;
                this.lastFetchedAt = Date.now();
            } catch {
                // Ads not available
            }
        },

        /** Force-refresh ads on next fetchAds call. */
        invalidate() {
            this.loaded = false;
            this.ads = [];
            this.lastFetchedAt = 0;
        },
    },
});
