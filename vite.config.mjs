import { createHash } from 'node:crypto';
import { readFileSync, rmSync, writeFileSync } from 'node:fs';
import { resolve } from 'node:path';
import { transformSync } from 'esbuild';
import { compile } from 'sass';
import { defineConfig } from 'vite';
import react from '@vitejs/plugin-react';

const publicPath = 'assets/dist';
const manifestPath = resolve(publicPath, 'mix-manifest.json');
const versionedFiles = [
    '/js/builder.ext.js',
    '/js/core.js',
    '/css/core.css',
    '/js/global.js',
];

function mixManifest({ minifyCss }) {
    return {
        name: 'mix-manifest',
        buildStart() {
            this.addWatchFile(resolve('assets/sass/core.scss'));

            let css = compile('assets/sass/core.scss', {
                style: 'expanded',
            }).css;

            if (minifyCss) {
                css = transformSync(css, {
                    loader: 'css',
                    minify: true,
                }).code;
            }

            this.emitFile({
                type: 'asset',
                fileName: 'css/core.css',
                source: css,
            });
        },
        generateBundle(_, bundle) {
            delete bundle['js/core-css.js'];
        },
        closeBundle() {
            rmSync(resolve(publicPath, 'js/core-css.js'), { force: true });
            rmSync(resolve(publicPath, 'assets/core-css.css'), { force: true });

            const manifest = versionedFiles.reduce((files, file) => {
                const outputFile = resolve(publicPath, file.slice(1));
                const hash = createHash('md5')
                    .update(readFileSync(outputFile))
                    .digest('hex')
                    .slice(0, 20);

                files[file] = `${file}?id=${hash}`;

                return files;
            }, {});

            writeFileSync(manifestPath, `${JSON.stringify(manifest, null, 4)}\n`);
        },
    };
}

export default defineConfig(({ mode }) => ({
    plugins: [
        react({
            jsxRuntime: 'classic',
        }),
        mixManifest({
            minifyCss: mode === 'production',
        }),
    ],
    build: {
        outDir: publicPath,
        emptyOutDir: false,
        manifest: false,
        minify: mode === 'production',
        cssMinify: false,
        cssCodeSplit: true,
        rollupOptions: {
            input: {
                core: resolve('assets/js/core.js'),
                'builder.ext': resolve('assets/js/builder.ext.jsx'),
                global: resolve('assets/js/global.js'),
            },
            output: {
                entryFileNames: 'js/[name].js',
                chunkFileNames: 'js/[name].js',
                assetFileNames: (assetInfo) => {
                    if (assetInfo.name === 'core.css' || assetInfo.name === 'core-css.css') {
                        return 'css/core.css';
                    }

                    return 'assets/[name][extname]';
                },
            },
        },
    },
    css: {
        preprocessorOptions: {
            scss: {
                style: 'expanded',
                outputStyle: 'expanded',
            },
        },
    },
}));
