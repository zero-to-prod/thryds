import { defineConfig } from 'vite';
import tailwindcss from '@tailwindcss/vite';

export default defineConfig({
    plugins: [tailwindcss()],
    build: {
        outDir: 'public/build',
        manifest: true,
        rollupOptions: {
            // CSS is imported via app.js (not listed separately). In production, Vite bundles it
            // into the manifest under the JS entry's "css" key. In dev, Vite serves CSS separately —
            // see Vite.php entry_css mapping which injects <link> tags for each CSS source path.
            input: ['resources/js/app.js', 'resources/js/htmx.js'],
        },
    },
    server: {
        host: '0.0.0.0',
        port: 5173,
        strictPort: true,
        origin: 'http://localhost:5173', // Must match Vite.php DEV_SERVER_URL
    },
});
