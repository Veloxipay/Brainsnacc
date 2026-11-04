import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';

// StudyLab's pages use the plain stylesheet in public/css/app.css,
// so Vite only builds the small default bundle.
export default defineConfig({
    plugins: [
        laravel({
            input: ['resources/css/app.css', 'resources/js/app.js'],
            refresh: true,
        }),
    ],
    server: {
        watch: {
            ignored: ['**/storage/framework/views/**'],
        },
    },
});
