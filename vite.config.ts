import { defineConfig } from 'vite';

/**
 * Vite configuration for Password Strength Bundle frontend assets.
 */
export default defineConfig({
  define: {
    __PASSWORD_STRENGTH_BUILD_TIME__: JSON.stringify(new Date().toISOString()),
  },
  build: {
    outDir: 'src/Resources/public',
    emptyOutDir: false,
    rollupOptions: {
      input: 'src/Resources/assets/src/password-strength.ts',
      output: {
        format: 'iife',
        entryFileNames: 'password-strength.js',
        assetFileNames: 'password-strength.[ext]',
      },
    },
    minify: true,
    sourcemap: false,
  },
});
