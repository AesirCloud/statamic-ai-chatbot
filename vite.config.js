import vue from '@vitejs/plugin-vue';
import laravel from 'laravel-vite-plugin';
import { defineConfig } from 'vite';

export default defineConfig({
    plugins: [
        vue(),
        laravel({
            input: [
                'resources/js/widget.js',
                'resources/css/widget.css',
            ],
            publicDirectory: 'public',
            buildDirectory: 'build',
            refresh: false,
        }),
    ],
    build: {
        manifest: false,
        emptyOutDir: false,
        rollupOptions: {
            output: {
                entryFileNames: '[name].js',
                chunkFileNames: 'chunks/[name].js',
                assetFileNames: '[name][extname]',
            },
        },
    },
});
