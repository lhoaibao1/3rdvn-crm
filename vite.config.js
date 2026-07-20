import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';

export default defineConfig({
    build: {
        rolldownOptions: {
            preserveEntrySignatures: 'strict',
        },
    },
    plugins: [
        laravel({
            input: [
                'resources/css/app.css',
                'resources/css/wirechat.css',
                'resources/css/searchable-select.css',
                'resources/js/app.js',
                'resources/js/echo.js',
                'resources/js/components/searchable-select.js',
            ],
            refresh: true,
        }),
    ],
});
