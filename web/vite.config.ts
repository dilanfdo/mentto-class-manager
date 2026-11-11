import tailwindcss from '@tailwindcss/vite'
import react from '@vitejs/plugin-react'
import { defineConfig } from 'vite'

// In development /api is proxied to Laravel (php artisan serve). In production
// a rewrite on the host does the same, so the browser only talks to one origin.
export default defineConfig({
  plugins: [react(), tailwindcss()],
  server: {
    proxy: { '/api': 'http://127.0.0.1:8010' },
  },
})
