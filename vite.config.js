import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import tailwindcss from '@tailwindcss/vite';
import vue from '@vitejs/plugin-vue';

export default defineConfig({
    build: {
        sourcemap: false,
    },
    plugins: [
        vue(),
        tailwindcss(),
        laravel({
            input: ['resources/css/tailwind.css', 'resources/css/app.scss', 'resources/js/app.js', 'resources/js/admin/admin.js'],
            refresh: true,
        }),
    ],
    server: {
        watch: {
            ignored: ['**/storage/framework/views/**'],
        },
    },
});
