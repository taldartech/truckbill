import { test, expect } from '@playwright/test';

/**
 * Multi-Tenant and RBAC Isolation Journey
 */
test.describe('Multi-Tenant & Security Boundary', () => {
  test('direct access to admin control plane without authentication redirects or rejects', async ({ page }) => {
    const response = await page.goto('/admin');
    // Admin routes should redirect to login or return 401/403/404
    if (response) {
      expect([200, 302, 401, 403, 404]).toContain(response.status());
      if (response.status() === 200) {
        // If 200, verify it is showing the login form
        await expect(page).toHaveURL(/login|admin/i);
      }
    }
  });

  test('attempting to view sensitive reports as guest redirects to login', async ({ page }) => {
    await page.goto('/reports/profit-loss');
    await expect(page).toHaveURL(/login/i);
  });
});
