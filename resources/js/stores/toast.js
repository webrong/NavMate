import { defineStore } from 'pinia';

export const useToastStore = defineStore('toast', {
    state: () => ({
        items: [],
        nextId: 0,
    }),

    actions: {
        show(message, type = 'success', duration = 3000) {
            const id = this.nextId++;
            this.items.push({ id, message, type });
            if (duration > 0) {
                setTimeout(() => this.remove(id), duration);
            }
        },

        success(message, duration = 3000) {
            this.show(message, 'success', duration);
        },

        error(message, duration = 4000) {
            this.show(message, 'error', duration);
        },

        info(message, duration = 3000) {
            this.show(message, 'info', duration);
        },

        remove(id) {
            const index = this.items.findIndex((t) => t.id === id);
            if (index !== -1) this.items.splice(index, 1);
        },
    },
});
