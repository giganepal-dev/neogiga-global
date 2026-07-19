const { chromium } = require('playwright');
const fs = require('fs');
const crypto = require('crypto');
const path = require('path');

const MANUFACTURERS = {
  'Murata Electronics': {
    searchUrl: (mpn) => `https://www.murata.com/en-us/search/productsearch?search=${encodeURIComponent(mpn)}`,
    productSelector: 'a[href*="/products/detail"]',
    imageSelector: 'img[src*="product"], img[src*="image"], .product-image img, .detail-image img',
  },
  'Vishay Intertech': {
    searchUrl: (mpn) => `https://www.vishay.com/search?searchWord=${encodeURIComponent(mpn)}`,
    productSelector: '.search-result-item a, a[href*="/product/"]',
    imageSelector: 'img[src*="product"], img[src*="photo"], .product-photo img',
  },
};

async function scrapeImages(mfr, mpn, outputDir) {
  const config = MANUFACTURERS[mfr];
  if (!config) { console.log(`No config for ${mfr}`); return []; }

  const browser = await chromium.launch({ headless: true });
  const page = await browser.newPage();
  const images = [];

  try {
    const url = config.searchUrl(mpn);
    console.log(`  ${mfr}: ${url}`);
    await page.goto(url, { waitUntil: 'networkidle', timeout: 20000 });
    await page.waitForTimeout(2000);

    // Try clicking first product result
    const firstResult = await page.$(config.productSelector);
    if (firstResult) {
      await firstResult.click();
      await page.waitForTimeout(3000);
    }

    // Extract images
    const imgElements = await page.$$(config.imageSelector);
    for (const img of imgElements) {
      const src = await img.getAttribute('src');
      if (src && (src.includes('product') || src.includes('image') || src.includes('photo'))) {
        const fullUrl = src.startsWith('http') ? src : new URL(src, url).href;
        images.push(fullUrl);
      }
    }

    if (images.length === 0) {
      // Fallback: get all img tags
      const allImgs = await page.$$eval('img', imgs =>
        imgs.filter(i => i.naturalWidth > 100 && i.naturalHeight > 100)
             .map(i => i.src)
      );
      images.push(...allImgs);
    }
  } catch (e) {
    console.log(`  Error: ${e.message}`);
  } finally {
    await browser.close();
  }

  return [...new Set(images)];
}

// CLI: node scrape-images.js "Murata Electronics" "MPN123" /output/dir
(async () => {
  const [,, mfr, mpn, outputDir] = process.argv;
  if (!mfr || !mpn) { console.log('Usage: node scrape-images.js <manufacturer> <mpn>'); process.exit(1); }
  const dir = outputDir || '/tmp/neogiga-images';
  fs.mkdirSync(dir, { recursive: true });

  const images = await scrapeImages(mfr, mpn, dir);
  console.log(`Found ${images.length} images`);
  images.forEach(i => console.log(`IMAGE: ${i}`));
})();
