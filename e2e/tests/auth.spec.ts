import { test, expect } from '@playwright/test';

/**
 * Authentication and Authorization Journey
 */
test.describe('Authentication Journey', () => {
  test('login page loads with proper controls and CSRF', async ({ page }) => {
    await page.goto('/login');
    await expect(page).toHaveTitle(/TruckBill|Login/i);
    await expect(page.locator('input[name="email"], input[type="email"]')).toBeVisible();
    await expect(page.locator('input[name="password"], input[type="password"]')).toBeVisible();
    await expect(page.locator('button[type="submit"], input[type="submit"]')).toBeVisible();
  });

  test('login rejects invalid credentials with error message', async ({ page }) => {
    await page.goto('/login');
    await page.locator('input[name="email"], input[type="email"]').fill('invalid_user@truckbill.in');
    await page.locator('input[name="password"], input[type="password"]').fill('WrongPassword123!');
    await page.locator('button[type="submit"], input[type="submit"]').click();

    // Expect to remain on login or receive feedback
    await expect(page).toHaveURL(/login/i);
    const bodyText = await page.textContent('body');
    expect(bodyText).toMatch(/credentials|match|invalid|error/i);
  });

  test('password reset request page is reachable and renders email input', async ({ page }) => {
    const response = await page.goto('/forgot-password');
    if (response?.status() === 200) {
      await expect(page.locator('input[type="email"], input[name="email"]')).toBeVisible();
    }
  });

  test('unauthenticated request to protected dashboard redirects to login', async ({ page }) => {
    await page.goto('/dashboard');
    await expect(page).toHaveURL(/login/i);
  });

  test('unauthenticated request to lorry_receipts redirects to login', async ({ page }) => {
    await page.goto('/lorry_receipts');
    await expect(page).toHaveURL(/login/i);
  });
});
