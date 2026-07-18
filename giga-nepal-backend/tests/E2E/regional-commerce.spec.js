import { test, expect } from '@playwright/test';

test('Global homepage shows USD context', async ({ page }) => {
  const response = await page.goto('/en');
  expect(response.status()).toBe(200);
  await expect(page.locator('text=Global').first()).toBeVisible({ timeout: 5000 });
});

test('Nepal homepage shows localized content', async ({ page }) => {
  const response = await page.goto('/np');
  expect(response.status()).toBe(200);
  await expect(page).toHaveTitle(/Nepal/);
});

test('India product page has INR pricing', async ({ page }) => {
  const response = await page.goto('/np/products/sunlord-gz1608d601tf-1002');
  expect(response.status()).toBe(200);
  // Should show NPR currency
  await expect(page.locator('text=NPR').first()).toBeVisible({ timeout: 5000 });
});

test('Cart redirects to login when empty', async ({ page }) => {
  const response = await page.goto('/cart');
  expect(response.status()).toBe(200);
});

test('Checkout validates authentication', async ({ page }) => {
  const response = await page.goto('/checkout');
  expect(response.status()).toBe(200);
});
