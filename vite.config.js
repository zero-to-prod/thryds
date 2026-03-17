import { defineConfig } from 'vite';
import tailwindcss from '@tailwindcss/vite';
import { compression } from 'vite-plugin-compression2';
import zlib from 'node:zlib';

export default defineConfig({
    plugins: [
        tailwindcss(),
        compression({ algorithm: 'gzip', level: 9 }),
        compression({ algorithm: 'brotliCompress', params: { [zlib.constants.BROTLI_PARAM_QUALITY]: 11 } }),
        compression({ algorithm: 'zstd' }),
    ],
    publicDir: false,
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