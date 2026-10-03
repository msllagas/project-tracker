import { fileURLToPath, URL } from 'node:url'
import tailwindcss from '@tailwindcss/vite'
import vue from '@vitejs/plugin-vue'
import { defineConfig } from 'vite'

// Laravel's address; Docker points this at the backend container.
const apiTarget = process.env.API_PROXY_TARGET ?? 'http://localhost:8000'

// https://vite.dev/config/
export default defineConfig({
  plugins: [vue(), tailwindcss()],
  resolve: {
    alias: {
      '@': fileURLToPath(new URL('./src', import.meta.url)),
    },
  },
  server: {
    // Sanctum only trusts this origin for cookie auth, so fail loudly instead of switching ports.
    port: 5173,
    strictPort: true,
    // Serve the API from the same origin as the SPA so Sanctum's session cookies just work.
    proxy: {
      '/api': apiTarget,
      '/sanctum': apiTarget,
    },
  },
})
