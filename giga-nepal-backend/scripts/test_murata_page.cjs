const { chromium } = require('playwright');

(async () => {
  const browser = await chromium.launch({ headless: true });
  const page = await browser.newPage();
  
  // Try Murata search with networkidle
  await page.goto('https://www.murata.com/en-us/search/productsearch?search=BLM21BB600SN1D', 
    { waitUntil: 'networkidle', timeout: 20000 });
  
  // Get all links on the page
  const links = await page.$$eval('a[href*="product"]', els => els.map(e => ({href: e.href, text: e.textContent.trim().substring(0,60)})));
  console.log('Product links found:', links.length);
  links.slice(0,5).forEach(l => console.log('  ', l.href));

  // Get all images > 150px
  const imgs = await page.$$eval('img', els => els.filter(i => i.naturalWidth > 150).map(i => ({src: i.src, w: i.naturalWidth, h: i.naturalHeight, alt: i.alt?.substring(0,40)})));
  console.log('\nLarge images:', imgs.length);
  imgs.forEach(i => console.log(`  ${i.w}x${i.h} ${i.src.substring(0,80)}`));

  await browser.close();
})();
