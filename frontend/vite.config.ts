import path from 'node:path'
import tailwindcss from '@tailwindcss/vite'
import react from '@vitejs/plugin-react'
import { defineConfig } from 'vite'

// https://vite.dev/config/
export default defineConfig({
  plugins: [react(), tailwindcss()],
  resolve: {
    alias: {
      '@': path.resolve(import.meta.dirname, './src'),
    },
  },
  server: {
    // Must match the backend's FRONTEND_URL / SANCTUM_STATEFUL_DOMAINS.
    // Use "localhost" (not 127.0.0.1) so the session cookie is shared with the API.
    host: 'localhost',
    port: 5173,
    strictPort: true,
  },
})
