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

  test('strict style CSP demo: the injected base <style> carries the request nonce', async ({ page }) => {
    const violations: string[] = [];
    page.on('console', (msg) => {
      if (msg.type() === 'error' && /Content Security Policy|CSP/i.test(msg.text())) violations.push(msg.text());
    });
    const response = await page.goto('/en/demo/csp');
    expect(response?.ok()).toBeTruthy();
    // The form type stamps the request nonce on the widget root and the input.
    const nonce = (await page.locator('input[data-password-strength-nonce]').first().getAttribute('data-password-strength-nonce')) ?? '';
    expect(nonce).not.toBe('');
    await expect(page.locator('nowo-password-strength[data-nonce]').first()).toHaveAttribute('data-nonce', nonce);
    await expect(page.locator('#password-strength-inline-styles')).toHaveCount(1);
    expect(await page.locator('#password-strength-inline-styles').evaluate((el) => (el as HTMLStyleElement).nonce)).toBe(nonce);
    expect(violations).toEqual([]);
  });
});
