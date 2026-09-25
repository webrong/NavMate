import { defineStore } from 'pinia';
import request from '../utils/request';

export const useLayoutStore = defineStore('layout', {
    state: () => ({
        data: [],
        loaded: false,
    }),

    actions: {
        async fetchLayout() {
            try {
                const { data } = await request.get('/api/user/layout');
                this.data = data;
            } catch {
                this.data = [];
            } finally {
                this.loaded = true;
            }
        },

        async saveLayout(layoutData) {
            // No catch here: callers must be able to tell success from
            // failure (the interceptor already toasts the error)
            await request.put('/api/user/layout', { layout_data: layoutData });
            this.data = layoutData;
        },

        clear() {
            this.data = [];
            this.loaded = false;
        },
    },
});
