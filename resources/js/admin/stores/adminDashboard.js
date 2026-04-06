import { defineStore } from 'pinia';
import request from '../utils/request';

export const useAdminDashboardStore = defineStore('adminDashboard', {
    state: () => ({
        stats: null,
        recentSites: [],
        topSites: [],
        loading: false,
    }),

    actions: {
        async fetchData() {
            this.loading = true;
            try {
                const { data } = await request.get('/admin/api/dashboard');
                this.stats = data.stats;
                this.recentSites = data.recent_sites;
                this.topSites = data.top_sites;
            } finally {
                this.loading = false;
            }
        },
    },
});
