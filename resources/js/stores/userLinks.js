import { defineStore } from 'pinia';
import request from '../utils/request';

export const useUserLinksStore = defineStore('userLinks', {
    state: () => ({
        links: [],
        loaded: false,
    }),

    actions: {
        async fetchLinks() {
            try {
                const { data } = await request.get('/api/user/links');
                this.links = data;
            } catch {
                this.links = [];
            } finally {
                this.loaded = true;
            }
        },

        async addLink(url, title) {
            const { data } = await request.post('/api/user/links', { url, title });
            this.links.push(data);
            return data;
        },

        async removeLink(id) {
            this.links = this.links.filter((l) => l.id !== id);
            try {
                await request.delete(`/api/user/links/${id}`);
            } catch {
                await this.fetchLinks();
            }
        },

        async updateLink(id, updates) {
            const link = this.links.find((l) => l.id === id);
            if (link) {
                Object.assign(link, updates);
            }
            try {
                await request.put(`/api/user/links/${id}`, updates);
            } catch {
                await this.fetchLinks();
            }
        },

        async reorder(items) {
            try {
                await request.put('/api/user/links/reorder', { items });
                await this.fetchLinks();
            } catch {
                await this.fetchLinks();
            }
        },

        clear() {
            this.links = [];
            this.loaded = false;
        },
    },
});
