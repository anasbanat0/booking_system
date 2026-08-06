import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';

export default defineConfig({
    plugins: [
        laravel({
            input: [
                'resources/css/app.css',
                'resources/js/app.js',
                'resources/images/logo.png',
                'resources/images/IMG_0491.webp',
                'resources/images/IMG_0605.webp',
                'resources/images/IMG_0612.webp',
                'resources/images/IMG_0623.webp',
                'resources/images/IMG_0642.webp',
            ],
            refresh: true,
        }),
    ],
});
