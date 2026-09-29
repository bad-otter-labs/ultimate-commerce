import { test, expect } from '@playwright/test';
import AxeBuilder from '@axe-core/playwright';

async function expectNoUcAxeViolations(page, selectors) {
  let builder = new AxeBuilder({ page }).withTags([
    'wcag2a',
    'wcag2aa',
    'wcag21a',
    'wcag21aa',
    'wcag22a',
    'wcag22aa',
  ]);

  for (const selector of selectors) {
    builder = builder.include(selector);
  }

  const results = await builder.analyze();
  expect(
    results.violations,
    JSON.stringify(
      results.violations.map((violation) => ({
        id: violation.id,
        impact: violation.impact,
        help: violation.help,
        nodes: violation.nodes.map((node) => ({
          target: node.target,
          summary: node.failureSummary,
        })),
      })),
      null,
      2
    )
  ).toEqual([]);
}

test.beforeEach(async ({ page }) => {
  await page.goto('/');
  await page.waitForLoadState('networkidle');
  await expect(page.locator('[data-uc-a11y-fixture="1"]')).toBeVisible();
});

test('UC storefront surfaces pass the scoped WCAG axe baseline', async ({ page }) => {
  await expectNoUcAxeViolations(page, [
    '[data-uc-a11y-fixture="1"]',
    '[data-uc-wishlist-list="1"]',
    '[data-uc-recently-viewed-list="1"]',
  ]);
});

test('variation radios support arrow-key selection and focus movement', async ({ page }) => {
  const radios = page.locator('.uc-variation-option[role="radio"]');
  await expect(radios).toHaveCount(2);

  await expect(radios.first()).toHaveAttribute('tabindex', '0');
  await expect(radios.nth(1)).toHaveAttribute('tabindex', '-1');

  await radios.first().focus();
  await expect(radios.first()).toBeFocused();

  await page.keyboard.press('ArrowRight');

  await expect(radios.nth(1)).toBeFocused();
  await expect(radios.nth(1)).toHaveAttribute('aria-checked', 'true');
  await expect(radios.nth(1)).toHaveAttribute('tabindex', '0');
  await expect(radios.first()).toHaveAttribute('aria-checked', 'false');
  await expect(radios.first()).toHaveAttribute('tabindex', '-1');
  await expect(page.locator('[data-uc-variation-submit="1"]')).toBeEnabled();
});

test('cart drawer traps keyboard focus, closes with Escape and restores focus', async ({ page }) => {
  const trigger = page.locator('[data-uc-cart-toggle="1"]').first();
  await trigger.focus();
  await trigger.click();

  const drawer = page.locator('[data-uc-cart-drawer="1"]');
  const dialog = page.getByRole('dialog', { name: 'Your cart' });
  const close = page.getByRole('button', { name: 'Close cart' });

  await expect(drawer).toBeVisible();
  await expect(dialog).toBeVisible();
  await expect(trigger).toHaveAttribute('aria-expanded', 'true');
  await expect(close).toBeFocused();

  await expectNoUcAxeViolations(page, ['[data-uc-cart-drawer="1"]']);

  const focusables = dialog.locator(
    'a[href]:not([tabindex="-1"]), button:not([disabled]), input:not([disabled]), select:not([disabled]), textarea:not([disabled]), [tabindex]:not([tabindex="-1"])'
  );
  const last = focusables.last();

  await page.keyboard.press('Shift+Tab');
  await expect(last).toBeFocused();

  await page.keyboard.press('Tab');
  await expect(close).toBeFocused();

  await page.keyboard.press('Escape');
  await expect(drawer).toBeHidden();
  await expect(trigger).toBeFocused();
  await expect(trigger).toHaveAttribute('aria-expanded', 'false');
});

test('wishlist pressed state and recently viewed live UI remain operable', async ({ page }) => {
  const fixture = page.locator('[data-uc-a11y-fixture="1"]');
  const recentProductId = Number(await fixture.getAttribute('data-recent-product-id'));
  expect(recentProductId).toBeGreaterThan(0);

  const wishlistToggle = page.locator('[data-uc-wishlist-toggle="1"]').first();
  await expect(wishlistToggle).toHaveAttribute('aria-pressed', 'false');
  await wishlistToggle.click();
  await expect(wishlistToggle).toHaveAttribute('aria-pressed', 'true');
  await expect(wishlistToggle).toHaveAttribute('aria-label', /Remove .* from wishlist|Remove from wishlist/);

  const wishlistIds = await page.evaluate(() => {
    return JSON.parse(window.localStorage.getItem('ulticofo_wishlist_v1') || '[]');
  });
  expect(wishlistIds.length).toBeGreaterThan(0);
  await expect.poll(async () => page.evaluate(() => window.localStorage.getItem('uc_wishlist_v1'))).toBeNull();

  await page.evaluate((id) => {
    window.localStorage.setItem('uc_recently_viewed_v1', JSON.stringify([id]));
  }, recentProductId);
  await page.reload();
  await page.waitForLoadState('networkidle');

  await expect(page.locator('.uc-recently-viewed-item')).toHaveCount(1);
  await expect(page.locator('[data-uc-recently-viewed-status="1"]')).toContainText('1 recently viewed product');
  const migratedRecent = await page.evaluate(() => JSON.parse(window.localStorage.getItem('ulticofo_recently_viewed_v1') || '[]'));
  expect(migratedRecent).toContain(recentProductId);
  await expect.poll(async () => page.evaluate(() => window.localStorage.getItem('uc_recently_viewed_v1'))).toBeNull();

  await expectNoUcAxeViolations(page, [
    '[data-uc-wishlist-list="1"]',
    '[data-uc-recently-viewed-list="1"]',
  ]);

  await page.locator('[data-uc-recently-viewed-clear="1"]').click();
  await expect(page.locator('.uc-recently-viewed-item')).toHaveCount(0);
  await expect(page.locator('[data-uc-recently-viewed-status="1"]')).toContainText('Recently viewed products cleared');

  const recentState = await page.evaluate(() => window.localStorage.getItem('ulticofo_recently_viewed_v1'));
  const legacyRecentState = await page.evaluate(() => window.localStorage.getItem('uc_recently_viewed_v1'));
  expect(recentState).toBeNull();
  expect(legacyRecentState).toBeNull();
});
