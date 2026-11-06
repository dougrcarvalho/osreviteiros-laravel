import { defineConfig, loadEnv } from 'vite';
import laravel from 'laravel-vite-plugin';
import tailwindcss from '@tailwindcss/vite';

export default defineConfig(({ mode }) => {
    const env = loadEnv(mode, process.cwd(), '');
    const port = Number(env.VITE_PORT || 5173);

    return {
        plugins: [
            laravel({
                input: ['resources/css/app.css', 'resources/js/app.js', 'resources/js/site.js'],
                refresh: true,
            }),
            tailwindcss(),
        ],
        server: {
            // Rodando dentro do container do Sail
            host: '0.0.0.0',
            port,
            strictPort: true,
            hmr: { host: 'localhost', port },
            watch: {
                ignored: ['**/storage/framework/views/**'],
            },
        },
    };
});
