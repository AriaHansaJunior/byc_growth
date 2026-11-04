import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import fs from 'fs';
import path from 'path';

export default defineConfig({
    plugins: [
        laravel({
            input: ['resources/css/app.css', 'resources/js/app.js'],
            refresh: true,
        }),
        {
            name: 'fixed-asset-filenames',
            closeBundle() {
                const buildDir = path.resolve(__dirname, 'public/build');
                const assetsDir = path.join(buildDir, 'assets');
                const manifestPath = path.join(buildDir, 'manifest.json');
                if (!fs.existsSync(manifestPath)) return;

                const manifest = JSON.parse(fs.readFileSync(manifestPath, 'utf-8'));

                if (manifest['resources/css/app.css']) {
                    const oldCss = path.join(buildDir, manifest['resources/css/app.css'].file);
                    const newCss = path.join(assetsDir, 'app.css');
                    if (fs.existsSync(oldCss) && oldCss !== newCss) {
                        fs.renameSync(oldCss, newCss);
                    }
                    manifest['resources/css/app.css'].file = 'assets/app.css';
                }

                if (manifest['resources/js/app.js']) {
                    const oldJs = path.join(buildDir, manifest['resources/js/app.js'].file);
                    const newJs = path.join(assetsDir, 'app.js');
                    if (fs.existsSync(oldJs) && oldJs !== newJs) {
                        fs.renameSync(oldJs, newJs);
                    }
                    manifest['resources/js/app.js'].file = 'assets/app.js';
                }

                // Remove any stray files in public/build/assets other than app.css and app.js
                for (const file of fs.readdirSync(assetsDir)) {
                    if (file !== 'app.css' && file !== 'app.js') {
                        fs.unlinkSync(path.join(assetsDir, file));
                    }
                }

                fs.writeFileSync(manifestPath, JSON.stringify(manifest, null, 2));
            },
        },
    ],
    build: {
        emptyOutDir: true,
    },
});
