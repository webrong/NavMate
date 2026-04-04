import { defineStore } from 'pinia';
import axios from 'axios';

export const useLayoutStore = defineStore('layout', {
    state: () => ({
        data: [],
        loaded: false,
    }),

    actions: {
        async fetchLayout() {
            try {
                const { data } = await axios.get('/api/user/layout');
                this.data = data;
            } catch {
                this.data = [];
            } finally {
                this.loaded = true;
            }
        },

        async saveLayout(layoutData) {
            try {
                await axios.put('/api/user/layout', { layout_data: layoutData });
                this.data = layoutData;
            } catch {
                // ignore
            }
        },

        clear() {
            this.data = [];
            this.loaded = false;
        },
    },
});
