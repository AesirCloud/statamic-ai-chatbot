import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import statamic from '@statamic/cms/vite-plugin';

export default defineConfig({
    plugins: [
        laravel({
            input: [
                'resources/js/cp.js',
                'resources/css/cp.css',
            ],
            publicDirectory: 'public',
            buildDirectory: 'build',
            refresh: false,
        }),
        statamic(),
    ],
    build: {
        // Statamic addon asset publishing expects the manifest at the build root.
        manifest: 'manifest.json',
        rollupOptions: {
            output: {
                entryFileNames: '[name].js',
                chunkFileNames: 'chunks/[name].js',
                assetFileNames: '[name][extname]',
            },
        },
    },
});
