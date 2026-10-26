import { test, expect } from '@playwright/test';

/**
 * Critical journey: login → dashboard.
 * Requires a running app at E2E_BASE_URL and credentials in e2e/.env:
 *   E2E_BASE_URL=https://broker.example.test
 *   E2E_EMAIL=admin@example.com
 *   E2E_PASSWORD=secret
 *
 * Skips when credentials are not configured (safe for CI without secrets).
 */
test.describe('Authentication', () => {
  test('admin can sign in and reach the dashboard', async ({ page }) => {
    const email = process.env.E2E_EMAIL;
    const password = process.env.E2E_PASSWORD;

    test.skip(!email || !password, 'E2E_EMAIL / E2E_PASSWORD not configured');

    await page.goto('/login');
    await expect(page.getByRole('heading', { name: /login|sign in/i }).or(page.locator('form'))).toBeVisible();

    await page.getByLabel(/email/i).fill(email!);
    await page.locator('input[type="password"]').fill(password!);
    await page.getByRole('button', { name: /log\s*in|sign\s*in/i }).click();

    await expect(page).toHaveURL(/dashboard/i, { timeout: 20_000 });
  });

  test('login page rejects empty credentials', async ({ page }) => {
    await page.goto('/login');
    await page.getByRole('button', { name: /log\s*in|sign\s*in/i }).click();
    await expect(page).toHaveURL(/login/i);
  });
});
