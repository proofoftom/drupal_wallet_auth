import { defineConfig } from 'vite';

export default defineConfig({
  build: {
    lib: {
      entry: './js/src/wallet-auth-connector.js',
      name: 'WalletAuthConnector',
      formats: ['iife'],
      fileName: 'wallet-auth-connector',
    },
    outDir: 'js/dist',
    emptyOutDir: false,
  },
});

// Also build UI separately
export default defineConfig({
  build: {
    lib: {
      entry: './js/src/wallet-auth-ui.js',
      name: 'WalletAuthUI',
      formats: ['iife'],
      fileName: 'wallet-auth-ui',
    },
    outDir: 'js/dist',
    emptyOutDir: false,
  },
});
