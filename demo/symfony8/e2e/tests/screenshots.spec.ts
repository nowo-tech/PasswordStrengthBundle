import { test, expect } from '@playwright/test';
import { mkdirSync } from 'node:fs';
import { resolve } from 'node:path';

/**
 * REQ-DEMO-013 — crop to the demo use-case panel (title + form + widget),
 * not a naked control and not full-page chrome (nav / back link / profiler).
 */
const outDir = process.env.SCREENSHOT_DIR
  ? resolve(process.env.SCREENSHOT_DIR)
  : resolve(__dirname, '../../../../docs/images/demo');

/** Demo page card: badges, title, lead, form panel with the password widget. */
function useCasePanel(page: import('@playwright/test').Page) {
  return page.locator('.demo-page-card').first();
}

test.beforeAll(() => {
  mkdirSync(outDir, { recursive: true });
});

test.describe('PasswordStrength screenshots (use-case context)', () => {
  test('overview — live requirements (met) in demo panel', async ({ page }) => {
    await page.goto('/en/demo/level');
    const panel = useCasePanel(page);
    await expect(panel).toBeVisible();
    const password = panel.locator('input[type="password"], input[type="text"]').first();
    await password.fill('Str0ng!Pass');
    await expect(panel.locator('.password-strength-requirement--met, .text-success').first()).toBeVisible({
      timeout: 5000,
    });
    await panel.screenshot({ path: resolve(outDir, 'overview.png') });
  });

  test('interaction — unmet feedback in demo panel', async ({ page }) => {
    await page.goto('/en/demo/level');
    const panel = useCasePanel(page);
    await expect(panel).toBeVisible();
    const password = panel.locator('input[type="password"], input[type="text"]').first();
    await password.fill('ab');
    await expect(panel.locator('.password-strength-requirement--unmet, .text-danger').first()).toBeVisible({
      timeout: 5000,
    });
    await panel.screenshot({ path: resolve(outDir, 'interaction.png') });
  });
});
