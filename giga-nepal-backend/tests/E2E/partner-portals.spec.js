import { test, expect } from '@playwright/test';

const PORTALS = [
  { path: '/seller/login', title: 'Seller' },
  { path: '/distributor/login', title: 'Distributor' },
  { path: '/reseller/login', title: 'Reseller' },
  { path: '/manufacturer/login', title: 'Manufacturer' },
  { path: '/b2b/login', title: 'Business' },
];

for (const { path, title } of PORTALS) {
  test(`${path} renders login form`, async ({ page }) => {
    const response = await page.goto(path);
    expect(response.status()).toBe(200);
    await expect(page.locator('h1, h2').filter({ hasText: /Sign In|Login/i }).first()).toBeVisible({ timeout: 5000 });
    await expect(page.locator('input[type="email"]').first()).toBeVisible();
    await expect(page.locator('input[type="password"]').first()).toBeVisible();
    await expect(page.getByRole('button', { name: /Sign In|Login/i }).first()).toBeVisible();
  });
}
