import { chromium } from 'playwright';

const baseURL = process.env.UC_SCREENSHOT_BASE_URL || 'http://uc-web';
const browser = await chromium.launch({ headless: true });
const context = await browser.newContext({
  viewport: { width: 1440, height: 1000 },
  deviceScaleFactor: 1,
});
const page = await context.newPage();

async function login() {
  await page.goto(baseURL + '/wp-login.php');
  await page.locator('#user_login').fill('admin');
  await page.locator('#user_pass').fill('uc-screenshot-password');
  await Promise.all([
    page.waitForURL(/wp-admin/),
    page.locator('#wp-submit').click(),
  ]);
}

async function save(name, locator = null) {
  if (locator) {
    await locator.screenshot({ path: '/output/' + name });
  } else {
    await page.screenshot({ path: '/output/' + name, fullPage: true });
  }
}

await login();

await page.goto(baseURL + '/wp-admin/admin.php?page=ultimate-commerce');
await page.waitForLoadState('networkidle');
await save('screenshot-1.png', page.locator('#wpbody-content'));

await page.goto(baseURL + '/wp-admin/admin.php?page=ultimate-commerce-modules');
await page.waitForLoadState('networkidle');
await save('screenshot-2.png', page.locator('#wpbody-content'));

await page.goto(baseURL + '/');
await page.waitForLoadState('networkidle');
const fixture = page.locator('[data-uc-a11y-fixture="1"]');
await fixture.waitFor({ state: 'visible' });
const recentProductId = Number(await fixture.getAttribute('data-recent-product-id'));
if (recentProductId > 0) {
  await page.evaluate((id) => {
    window.localStorage.setItem('uc_recently_viewed_v1', JSON.stringify([id]));
  }, recentProductId);
  await page.reload();
  await page.waitForLoadState('networkidle');
}
const wishlist = page.locator('[data-uc-wishlist-toggle="1"]').first();
if (await wishlist.count()) {
  await wishlist.click();
  await page.waitForTimeout(300);
}
await save('screenshot-3.png', page.locator('.entry-content'));

const submit = page.locator('[data-uc-variation-submit="1"]').first();
if (await submit.isDisabled()) {
  await page.locator('.uc-variation-option[role="radio"]:not([disabled])').first().click();
  await page.waitForFunction(() => {
    const control = document.querySelector('[data-uc-variation-submit="1"]');
    return control && !control.disabled;
  });
}
await submit.click();
const drawer = page.locator('[data-uc-cart-drawer="1"]');
await drawer.waitFor({ state: 'visible' });
await page.locator('.uc-cart-item').first().waitFor({ state: 'visible' });
await save('screenshot-4.png');

await browser.close();
