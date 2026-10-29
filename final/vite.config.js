import { defineConfig } from 'vite'
import react from '@vitejs/plugin-react'

// https://vite.dev/config/
export default defineConfig({
  base: './',
  build: {
    rollupOptions: {
      input: ['index.html', 'dashboard.html'],
    },
  },
  plugins: [react()],
})
