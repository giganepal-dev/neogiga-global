import { test, expect } from '@playwright/test';

const PAGES = [
  { path: '/en', title: 'NeoGiga' },
  { path: '/login', title: 'Sign in' },
  { path: '/register', title: 'Create' },
  { path: '/cart', title: 'Cart' },
  { path: '/checkout', title: 'Checkout' },
  { path: '/en/rfq', title: 'RFQ' },
  { path: '/en/bom', title: 'BOM' },
  { path: '/en/categories/raspberry-pi', title: 'Raspberry Pi' },
  { path: '/en/products', title: 'Products', slow: true },
  { path: '/en/brands', title: 'Brands' },
];

for (const { path, title, slow } of PAGES) {
  test(`${path} loads with correct title`, async ({ page }) => {
    test.setTimeout(slow ? 60000 : 30000);
    const response = await page.goto(path, { timeout: slow ? 50000 : 20000 });
    expect(response.status()).toBe(200);
    await expect(page).toHaveTitle(new RegExp(title, 'i'), { timeout: slow ? 15000 : 5000 });
  });
}
