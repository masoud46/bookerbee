import { defineConfig, normalizePath } from 'vite'
import laravel from 'laravel-vite-plugin'

import glob from 'glob'
import path from 'node:path'
import fs from 'fs'
import { fileURLToPath } from 'node:url'

import { viteStaticCopy } from 'vite-plugin-static-copy';


const host = '127.0.0.1'
const port = '8000'

const input = Object.fromEntries(
	glob.sync('resources/js/pages/**/*.js').map(file => [

		// This remove `resources/js/pages/` as well as the file extension from each file, so e.g.
		// resources/js/pages/nested/foo.js becomes nested/foo
		path.relative('resources/js/pages', file.slice(0, file.length - path.extname(file).length)),
		fileURLToPath(new URL(file, import.meta.url))
	])
)
input['app'] = fileURLToPath(new URL('resources/js/app.js', import.meta.url))
input['auth'] = fileURLToPath(new URL('resources/js/auth.js', import.meta.url))
input['print-invoice'] = fileURLToPath(new URL('resources/scss/pages/print-invoice.scss', import.meta.url))

export default defineConfig({
	// server: {
	// 	https: {
	// 		key: fs.readFileSync('resources/crt/dev.localhost.key'),
	// 		cert: fs.readFileSync('resources/crt/dev.localhost.crt'),
	// 	},
	// 	proxy: {
	// 		'^(?!(\/\@vite|\/resources|\/node_modules))': {
	// 			target: `http://${host}:${port}`,
	// 		}
	// 	},
	// 	host,
	// 	port: 5173,
	// 	hmr: { host },
	// },

	resolve: {
		alias: {
			'~bootstrap': normalizePath(path.resolve(__dirname, 'node_modules/bootstrap')),
		}
	},

	build: {
		assetsInlineLimit: 0,
		rollupOptions: {
			output: {
				assetFileNames: (assetInfo) => {
					// Get file extension
					// TS shows asset name can be undefined so I'll check it and create directory named `compiled` just to be safe
					let extension = assetInfo.name?.split('.').at(1) ?? 'compiled'

					// This is optional but may be useful (I use it a lot)
					// All images (png, jpg, etc) will be compiled within `images` directory,
					// all svg files within `icons` directory
					// if (/png|jpe?g|gif|tiff|bmp|ico/i.test(extension)) {
					//     extension = 'images'
					// }

					// if (/svg/i.test(extension)) {
					//     extension = 'icons'
					// }

					// Basically this is CSS output (in your case)
					return `${extension}/[name]-[hash][extname]`
				},
				chunkFileNames: 'js/chunks/[name]-[hash].js', // all chunks output path
				entryFileNames: 'js/[name]-[hash].js' // all entrypoints output path
			}
		}
	},

	plugins: [
		viteStaticCopy({
			targets: [
				// { src: normalizePath(path.join(__dirname, '/resources/flags/*')), dest: normalizePath(path.join(__dirname, '/public/build/flags')) },
				// { src: normalizePath(path.join(__dirname, '/resources/images/*')), dest: normalizePath(path.join(__dirname, '/public/build/images')) },
				// { src: normalizePath(path.join(__dirname, '/resources/fonts/*')), dest: normalizePath(path.join(__dirname, '/public/build/fonts')) },
				{ src: 'resources/flags/*', dest: 'flags' },
				{ src: 'resources/images/*', dest: 'images' },
				{ src: 'resources/fonts/*', dest: 'fonts' },

				// { src: path.join(__dirname, '/resources/favicon'), dest: path.join(__dirname, '/public') },
				// { src: path.join(__dirname, '/resources/favicon/favicon.ico'), dest: path.join(__dirname, '/public') },
			],
		}),
		laravel({
			input,
			refresh: true,
		}),
	],

	// build: {
	// 	rollupOptions: {
	// 		output: {
	// 			manualChunks: id => {
	// 				if (id.includes('node_modules')) {
	// 					return id.toString().split('node_modules/')[1].split('/')[0].toString()
	// 				}

	// 				return 'vendor'
	// 			}
	// 		}
	// 	}
	// }

})
