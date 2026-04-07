import { defineStore } from 'pinia';
import request from '../utils/request';

export const useAdsStore = defineStore('ads', {
    state: () => ({
        ads: [],
        loaded: false,
    }),

    getters: {
        contentBetween: (state) => state.ads.filter(ad => ad.position === 'content_between'),
        sidebarBottom: (state) => state.ads.filter(ad => ad.position === 'sidebar_bottom'),
        footerAbove: (state) => state.ads.filter(ad => ad.position === 'footer_above'),
    },

    actions: {
        async fetchAds() {
            if (this.loaded) return;
            try {
                const { data } = await request.get('/api/ads');
                this.ads = data;
                this.loaded = true;
            } catch {
                // Ads not available
            }
        },
    },
});
