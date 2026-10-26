import { test, expect } from '@playwright/test';

/**
 * Freight Booking (Lorry Hire) Operational Journey
 */
test.describe('Freight Booking Lifecycle', () => {
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

  test('navigate to Freight Bookings list', async ({ page }) => {
    await page.goto('/freight_bookings');
    await expect(page.locator('table, .card, .content')).toBeVisible();
  });

  test('open create Freight Booking form and check calculation elements', async ({ page }) => {
    await page.goto('/freight_bookings/create');
    await expect(page.locator('form')).toBeVisible();
    // Look for truck, freight, advance, or balance fields
    const inputs = page.locator('input, select');
    expect(await inputs.count()).toBeGreaterThan(3);
  });
});
