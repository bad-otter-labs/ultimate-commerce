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
    return JSON.parse(window.localStorage.getItem('badotulc_wishlist_v1') || '[]');
  });
  expect(wishlistIds.length).toBeGreaterThan(0);

  await page.evaluate((id) => {
    window.localStorage.setItem('badotulc_recently_viewed_v1', JSON.stringify([id]));
  }, recentProductId);
  await page.reload();
  await page.waitForLoadState('networkidle');

  await expect(page.locator('.uc-recently-viewed-item')).toHaveCount(1);
  await expect(page.locator('[data-uc-recently-viewed-status="1"]')).toContainText('1 recently viewed product');

  await expectNoUcAxeViolations(page, [
    '[data-uc-wishlist-list="1"]',
    '[data-uc-recently-viewed-list="1"]',
  ]);

  await page.locator('[data-uc-recently-viewed-clear="1"]').click();
  await expect(page.locator('.uc-recently-viewed-item')).toHaveCount(0);
  await expect(page.locator('[data-uc-recently-viewed-status="1"]')).toContainText('Recently viewed products cleared');

  const recentState = await page.evaluate(() => window.localStorage.getItem('badotulc_recently_viewed_v1'));
  expect(recentState).toBeNull();
});


test('real Woo catalogue connects search, category facets, sorting, paging and native cart truth', async ({ page }) => {
  await page.locator('[data-uc-catalogue-fixture-link="1"]').click();
  await expect(page.locator('[data-uc-catalogue-fixture="1"]')).toBeVisible();

  const cards = page.locator('[data-uc-catalogue-fixture="1"] [data-uc-product-card="1"]');
  const filters = page.locator('[data-uc-catalog-filters="1"]');
  await expect(filters).toBeVisible();
  await expect(page.locator('[data-uc-catalog-sort="1"]')).toBeVisible();
  await expectNoUcAxeViolations(page, ['[data-uc-catalogue-fixture="1"]']);

  await filters.getByRole('searchbox', { name: 'Search products' }).fill('Catalogue Fixture');
  await filters.getByRole('button', { name: 'Apply filters' }).click();
  await expect(cards).toHaveCount(3);
  await expect(filters.getByRole('searchbox', { name: 'Search products' })).toHaveValue('Catalogue Fixture');

  await filters.locator('input[name="filter_category[]"][value="uc-a11y-fixture-jackets"]').check();
  await filters.getByRole('button', { name: 'Apply filters' }).click();
  await expect(cards).toHaveCount(2);
  await expect(cards.first()).toContainText('Catalogue Fixture Jacket');
  await expect(cards.nth(1)).toContainText('Catalogue Fixture Jacket');
  await expect(filters.locator('input[name="filter_category[]"][value="uc-a11y-fixture-jackets"]')).toBeChecked();

  // Exercise real Store API page navigation and assert the canonical search,
  // facet and per-page fields survive the next-page link.
  const pagedUrl = new URL(page.url());
  pagedUrl.searchParams.set('per_page', '1');
  await page.goto(pagedUrl.toString());
  await expect(cards).toHaveCount(1);
  const next = page.locator('[data-uc-catalog-pagination="1"] a[rel="next"]');
  await expect(next).toBeVisible();
  const nextHref = await next.getAttribute('href');
  expect(new URL(nextHref, page.url()).searchParams.get('uc_catalog_page')).toBe('2');
  await next.click();
  await expect(page).toHaveURL(/[?&]uc_catalog_page=2(?:&|$)/);
  await expect(cards).toHaveCount(1);
  await expect(filters.getByRole('searchbox', { name: 'Search products' })).toHaveValue('Catalogue Fixture');
  await expect(filters.locator('input[name="filter_category[]"][value="uc-a11y-fixture-jackets"]')).toBeChecked();

  // Sorting is a native GET, resets the page, and uses Woo's price order.
  const sorts = page.locator('[data-uc-catalog-sort="1"]');
  await sorts.getByRole('combobox', { name: 'Sort products' }).selectOption('price_asc');
  await sorts.getByRole('button', { name: 'Apply sort' }).click();
  await expect(cards).toHaveCount(1);
  await expect(cards.first()).toContainText('Catalogue Fixture Jacket Red');
  expect(new URL(page.url()).searchParams.get('uc_catalog_page')).toBeNull();
  expect(new URL(page.url()).searchParams.get('page')).toBeNull();

  // Price filtering narrows the real Woo query, while category/search/sort stay.
  await filters.locator('input[name="min_price"]').fill('20');
  await filters.getByRole('button', { name: 'Apply filters' }).click();
  await expect(cards).toHaveCount(1);
  await expect(cards.first()).toContainText('Catalogue Fixture Jacket Navy');
  await expect(page.locator('[data-uc-catalog-sort="1"] select[name="sort"]')).toHaveValue('price_asc');

  // A card's native Woo add-to-cart link must feed Woo's actual guest cart,
  // never a UC-owned duplicate total or session.
  await cards.first().locator('[data-uc-action="add_to_cart"]').click();
  await page.waitForLoadState('networkidle');

  const cart = await page.evaluate(async () => {
    const response = await fetch('/?rest_route=/wc/store/v1/cart', { credentials: 'same-origin' });
    return { status: response.status, body: await response.json() };
  });
  expect(cart.status).toBe(200);
  expect(Array.isArray(cart.body.items)).toBe(true);
  expect(cart.body.items.some((item) => item.name === 'Catalogue Fixture Jacket Navy' && item.quantity === 1)).toBe(true);
});
