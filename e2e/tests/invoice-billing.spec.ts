import { test, expect } from '@playwright/test';

/**
 * Customer Invoice & Billing Journey
 */
test.describe('Invoicing and Payment Operations', () => {
  const email = process.env.E2E_EMAIL;
  const password = process.env.E2E_PASSWORD;

  test.beforeEach(async ({ page }) => {
    test.skip(!email || !password, 'Skipping authenticated test: E2E_EMAIL/E2E_PASSWORD not set');
    await page.goto('/login');
    await page.locator('input[name="email"], input[type="email"]').fill(email!);
    await page.locator('input[name="password"], input[type="password"]').fill(password!);
    await page.locator('button[type="submit"], input[type="submit"]').click();
    await expect(page).toHaveURL(/dashboard/i, { timeout: 15_000 });
  });

  test('navigate to customer invoices list', async ({ page }) => {
    await page.goto('/customer_invoices');
    await expect(page.locator('table, .card, .content')).toBeVisible();
  });

  test('navigate to voucher payments list', async ({ page }) => {
    await page.goto('/voucher_payments');
    await expect(page.locator('table, .card, .content')).toBeVisible();
  });
});
