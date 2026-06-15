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
                this.stats = data.stats || {};
                // Always coerce to arrays — the table component calls
                // dataSource.some(...) internally and crashes hard if it ever
                // receives null / object / __PHP_Incomplete_Class from the API.
                this.recentSites = Array.isArray(data.recent_sites) ? data.recent_sites : [];
                this.topSites = Array.isArray(data.top_sites) ? data.top_sites : [];
            } catch (e) {
                // Keep previous (or empty) arrays on failure so the UI doesn't break.
                this.recentSites = [];
                this.topSites = [];
            } finally {
                this.loading = false;
            }
        },
    },
});
