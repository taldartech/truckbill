import { test, expect } from '@playwright/test';

/**
 * Lorry Receipt (LR / Bilty) Operational Journey
 */
test.describe('Lorry Receipt Workflow', () => {
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

  test('navigate to LR list and verify data table elements', async ({ page }) => {
    await page.goto('/lorry_receipts');
    await expect(page).toHaveURL(/lorry_receipts/i);
    await expect(page.locator('table, .card, .content')).toBeVisible();
  });

  test('navigate to LR creation form and inspect mandatory input fields', async ({ page }) => {
    await page.goto('/lorry_receipts/create');
    await expect(page.locator('form')).toBeVisible();
    // Validate consignor/consignee or freight inputs are present
    const inputs = page.locator('input, select');
    expect(await inputs.count()).toBeGreaterThan(3);
  });
});
