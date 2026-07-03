import { defineConfig } from 'vitest/config';

export default defineConfig({
  define: {
    __PASSWORD_STRENGTH_BUILD_TIME__: JSON.stringify('2026-01-15T12:00:00.000Z'),
  },
  test: {
    environment: 'happy-dom',
    globals: true,
    include: ['src/Resources/assets/**/*.test.ts'],
    coverage: {
      provider: 'v8',
      reporter: ['text', 'text-summary', 'html'],
      reportsDirectory: './coverage-ts',
      include: ['src/Resources/assets/src/password-pattern-builder.ts', 'src/Resources/assets/src/password-strength-lib.ts'],
      exclude: ['**/*.test.ts', '**/node_modules/**'],
    },
  },
});
