import { fileURLToPath } from 'node:url'
import vue from '@vitejs/plugin-vue'
import vueJsx from '@vitejs/plugin-vue-jsx'
import AutoImport from 'unplugin-auto-import/vite'
import Components from 'unplugin-vue-components/vite'
import { VueRouterAutoImports, getPascalCaseRouteName } from 'unplugin-vue-router'
import VueRouter from 'unplugin-vue-router/vite'
import { defineConfig, loadEnv } from 'vite'
import VueDevTools from 'vite-plugin-vue-devtools'
import MetaLayouts from 'vite-plugin-vue-meta-layouts'
import vuetify from 'vite-plugin-vuetify'
import svgLoader from 'vite-svg-loader'
import Layouts from 'vite-plugin-vue-layouts'
import { VitePWA } from 'vite-plugin-pwa' // Import the plugin
import packageJson from './package.json'

const buildVersion = new Date().getTime()

// https://vitejs.dev/config/
export default defineConfig(mode => {
  const env = loadEnv(mode, process.cwd(), '')

  // console.log('ENV CHECK:', env.VITE_BASE_COMPANY, env.VITE_APP_PWA_NAME) // 👈 add this
  return {
    plugins: [
      // Docs: https://github.com/posva/unplugin-vue-router
      // ℹ️ This plugin should be placed before vue plugin
      VueRouter({
        getRouteName: routeNode => {
          // Convert pascal case to kebab case
          return getPascalCaseRouteName(routeNode)
            .replace(/([a-z\d])([A-Z])/g, '$1-$2')
            .toLowerCase()
        },

      }),
      vue({
        template: {
          compilerOptions: {
            isCustomElement: tag => tag === 'swiper-container' || tag === 'swiper-slide',
          },
        },
      }),
      VueDevTools(),
      vueJsx(),
      Layouts(), // <--- Ensure it's used here

      VitePWA({
        registerType: 'autoUpdate',        // check & install updates in bg
        injectRegister: 'script-defer',            // injects the register code for you
        includeAssets: [`/logo/${env.VITE_BASE_COMPANY}/favicon.ico`, 'robots.txt'],
        selfDestroying: true,
        manifest: {
          name: env.VITE_APP_TITLE_NAME || 'Learning English',
          short_name: env.VITE_APP_SHORT_NAME || 'Learning English',
          description: env.VITE_APP_TITLE_NAME || 'Learning English',
          start_url: '/',                  // important for scope
          scope: '/',                      // make SW control whole app
          display: 'standalone',
          background_color: '#ffffff',
          theme_color: '#636363ff',
          icons: [
            { src: `/logo/${env.VITE_BASE_COMPANY}/logo-192.webp`, sizes: '192x192', type: 'image/webp' },
            { src: `/logo/${env.VITE_BASE_COMPANY}/logo-512.webp`, sizes: '512x512', type: 'image/webp' },
          ],
        },
        workbox: {
          skipWaiting: true,               // activate new SW immediately
          clientsClaim: true,              // take control of open tabs
          cleanupOutdatedCaches: true,
          globPatterns: ['**/*.{js,css,html,ico,png,svg,webp}'],
          navigateFallbackDenylist: [/^\/api\//], // don’t hijack API
          maximumFileSizeToCacheInBytes: 5_000_000, // your 5MB limit
        },

        // Optional: enable in dev to test updates
        // devOptions: { enabled: true }
      }),

      // Docs: https://github.com/vuetifyjs/vuetify-loader/tree/master/packages/vite-plugin
      vuetify({
        styles: {
          configFile: 'src/assets/styles/variables/_vuetify.scss',
        },
      }),



      // Docs: https://github.com/dishait/vite-plugin-vue-meta-layouts?tab=readme-ov-file
      MetaLayouts({
        target: './src/layouts',
        defaultLayout: 'default',
      }),

      // Docs: https://github.com/antfu/unplugin-vue-components#unplugin-vue-components
      Components({
        dirs: ['src/@core/components', 'src/views/demos', 'src/components'],
        dts: true,
        resolvers: [
          componentName => {
            // Auto import `VueApexCharts`
            if (componentName === 'VueApexCharts')
              return { name: 'default', from: 'vue3-apexcharts', as: 'VueApexCharts' }
          },
        ],
      }),

      // Docs: https://github.com/antfu/unplugin-auto-import#unplugin-auto-import
      AutoImport({
        imports: ['vue', VueRouterAutoImports, '@vueuse/core', '@vueuse/math', 'vue-i18n', 'pinia'],
        dirs: [
          './src/@core/utils',
          './src/@core/composable/',
          './src/composables/',
          './src/utils/',
          './src/plugins/*/composables/*',
        ],
        vueTemplate: true,

        // ℹ️ Disabled to avoid confusion & accidental usage
        ignore: ['useCookies', 'useStorage'],
        eslintrc: {
          enabled: true,
          filepath: './.eslintrc-auto-import.json',
        },
      }),
      svgLoader(),

      // Emits a small version.json the running app polls at runtime to
      // detect a newer deploy — independent of service-worker update
      // timing, which is unreliable on backgrounded/resumed iOS & Android
      // PWAs. See useBuildVersion.js.
      {
        name: 'emit-build-version',
        apply: 'build',
        generateBundle() {
          this.emitFile({
            type: 'asset',
            fileName: 'version.json',
            source: JSON.stringify({ version: buildVersion }),
          })
        },
      },
    ],
    define: {
      'process.env': {},
      __APP_BUILD_VERSION__: JSON.stringify(buildVersion),
      "__APP_VERSION__": JSON.stringify(packageJson.version)
    },
    resolve: {
      alias: {
        '@': fileURLToPath(new URL('./src', import.meta.url)),
        '@themeConfig': fileURLToPath(new URL('./themeConfig.js', import.meta.url)),
        '@core': fileURLToPath(new URL('./src/@core', import.meta.url)),
        '@layouts': fileURLToPath(new URL('./src/@layouts', import.meta.url)),
        '@images': fileURLToPath(new URL('./src/assets/images/', import.meta.url)),

        '@styles': fileURLToPath(new URL('./src/assets/styles/', import.meta.url)),
        '@configured-variables': fileURLToPath(new URL('./src/assets/styles/variables/_template.scss', import.meta.url)),
        '@db': fileURLToPath(new URL('./src/plugins/fake-api/handlers/', import.meta.url)),
        '@api-utils': fileURLToPath(new URL('./src/plugins/fake-api/utils/', import.meta.url)),
      },
    },
    build: {
      cssCodeSplit: true,
      chunkSizeWarningLimit: 5000,
      minify: 'esbuild',
      esbuild: {
        drop: ['console', 'debugger'],
      },
      rollupOptions: {
        output: {
          // Force chunk files to include a build timestamp/version
          chunkFileNames: `assets/[name]-[hash]-v${buildVersion}.js`,
          entryFileNames: `assets/[name]-[hash]-v${buildVersion}.js`,
          assetFileNames: `assets/[name]-[hash]-v${buildVersion}.[ext]`,
        },
      },
    },
    optimizeDeps: {
      exclude: ['vuetify'],
      entries: [
        './src/**/*.vue',
      ],
    },
    server: {
      proxy: {
        '/api': {
          target: env.VITE_API_URL || 'http://localhost:8000',
          changeOrigin: true,
        },
        '/oauth': {
          target: env.VITE_API_URL || 'http://localhost:8000',
          changeOrigin: true,
        },
      },
    },
  }
})
