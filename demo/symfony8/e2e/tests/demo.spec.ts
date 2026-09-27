import { test, expect } from '@playwright/test';

test.describe('PasswordStrength demo', () => {
  test('home lists demos', async ({ page }) => {
    const response = await page.goto('/en/');
    expect(response?.ok()).toBeTruthy();
    await expect(page.getByRole('heading', { level: 1 })).toBeVisible();
    await expect(page.getByRole('link', { name: /level|conditions|policy/i }).first()).toBeVisible();
  });

  test('level demo shows password field and accepts input', async ({ page }) => {
    await page.goto('/en/demo/level');
    const password = page.locator('input[type="password"], input[type="text"][name*="password" i]').first();
    await expect(password).toBeVisible();
    await password.fill('Aa1!xxxx');
    await expect(password).toHaveValue('Aa1!xxxx');
  });

  test('conditions demo renders form', async ({ page }) => {
    await page.goto('/en/demo/conditions');
    await expect(page.locator('form').first()).toBeVisible();
    await expect(page.locator('input[type="password"], input[type="text"]').first()).toBeVisible();
  });
});
