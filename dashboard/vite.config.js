import { defineConfig } from 'vite';
import react from '@vitejs/plugin-react';

export default defineConfig({
  plugins: [react()],
  server: {
    port: 5173,
    // Proxy optionnel : evite les soucis CORS en dev si besoin.
    proxy: {
      '/api': {
        target: 'http://localhost:8001',
        changeOrigin: true,
      },
      '/broadcasting': {
        target: 'http://localhost:8001',
        changeOrigin: true,
      },
    },
  },
});
