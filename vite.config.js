import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import tailwindcss from '@tailwindcss/vite';
import vue from '@vitejs/plugin-vue';

export default defineConfig({
    build: {
        sourcemap: false,
        rollupOptions: {
            output: {
                // Split long-lived vendor libraries into their own chunks so
                // app-code releases don't invalidate the browser cache for
                // the (large) UI framework code. (advancedChunks is the
                // Rolldown-native form; object-style manualChunks is gone.)
                advancedChunks: {
                    groups: [
                        { name: 'chunk-antd', test: /[\\/]node_modules[\\/]antdv-next[\\/]/ },
                        { name: 'chunk-echarts', test: /[\\/]node_modules[\\/]echarts[\\/]/ },
                        { name: 'chunk-vendor', test: /[\\/]node_modules[\\/](vue|vue-router|pinia|axios|dompurify|@vueuse)[\\/]/ },
                    ],
                },
            },
        },
    },
    plugins: [
        vue(),
        tailwindcss(),
        laravel({
            input: ['resources/css/tailwind.css', 'resources/js/app.js', 'resources/js/admin/admin.js'],
            refresh: true,
        }),
    ],
    server: {
        watch: {
            ignored: ['**/storage/framework/views/**'],
        },
    },
});
