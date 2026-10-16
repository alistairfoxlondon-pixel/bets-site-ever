import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import tailwindcss from '@tailwindcss/vite';

export default defineConfig({
    plugins: [
        laravel({
            input: ['resources/css/app.css', 'resources/js/app.js'],
            refresh: true,
        }),
        tailwindcss(),
    ],
    server: {
        host: '0.0.0.0',
        port: 5173,
        cors: true,
    },
    build: {
        // Keep the first paint small: the marketing pages never need the
        // heavier admin/interactive bundles.
        cssCodeSplit: true,
        chunkSizeWarningLimit: 900,
    },
});
