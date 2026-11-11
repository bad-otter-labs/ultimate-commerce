import { test, expect } from '@playwright/test';
import AxeBuilder from '@axe-core/playwright';

// Browser-only acceptance: checkout/order creation takes place in a disposable
// WordPress + WooCommerce site with the built-in non-payment BACS gateway.
test('Free variable Quick Add reaches a real Woo order through checkout', async ({ page }) => {
  await page.goto('/');
  await expect(page.locator('[data-uc-a11y-fixture="1"]')).toBeVisible();

  const fixture = page.locator('[data-uc-a11y-fixture="1"]');
  const card = fixture.locator('[data-uc-product-card="1"]');
  const productId = Number(await card.getAttribute('data-product-id'));
  expect(productId).toBeGreaterThan(0);

  await page.setViewportSize({ width: 390, height: 844 });
  await expect(card).toBeVisible();
  const radios = card.locator('.uc-variation-option[role="radio"]');
  await radios.first().focus();
  await page.keyboard.press('ArrowRight');
  await expect(radios.nth(1)).toBeFocused();
  await expect(radios.nth(1)).toHaveAttribute('aria-checked', 'true');

  const selectedValue = await card.locator('[data-uc-native-attribute-input]').inputValue();
  expect(selectedValue).toBe('Blue');
  const variationId = Number(await card.locator('input[name="variation_id"]').inputValue());
  expect(variationId).toBeGreaterThan(0);

  await card.locator('[data-uc-variation-submit="1"]').click();
  const drawer = page.locator('[data-uc-cart-drawer="1"]');
  await expect(drawer).toBeVisible();
  await expect(drawer.locator('[data-uc-cart-items="1"]')).toContainText('Accessible Variable Product');
  await expect(drawer.locator('[data-uc-cart-items="1"]')).toContainText('Blue');
  await expect(page.getByRole('button', { name: 'Close cart' })).toBeFocused();
  const a11y = await new AxeBuilder({ page }).include('[data-uc-cart-drawer="1"]').withTags([
    'wcag2a', 'wcag2aa', 'wcag21a', 'wcag21aa', 'wcag22a', 'wcag22aa',
  ]).analyze();
  expect(a11y.violations).toEqual([]);

  const cart = await page.evaluate(async () => {
    const response = await fetch('/?rest_route=/wc/store/v1/cart', { credentials: 'same-origin' });
    return { status: response.status, body: await response.json() };
  });
  expect(cart.status).toBe(200);
  const item = cart.body.items.find((entry) => entry.id === variationId);
  expect(item).toBeTruthy();
  expect(item.quantity).toBe(1);
  expect(item.variation.some((entry) => entry.value === 'Blue')).toBe(true);
  expect(Number(cart.body.totals.total_price)).toBeGreaterThan(0);

  const checkout = drawer.locator('[data-uc-cart-checkout="1"]');
  await expect(checkout).toHaveAttribute('aria-disabled', 'false');
  const checkoutHref = await checkout.getAttribute('href');
  const checkoutUrl = new URL(checkoutHref, page.url());
  expect(checkoutUrl.origin).toBe(new URL(page.url()).origin);
  await checkout.click();

  // WordPress plain permalinks legitimately use ?page_id= rather than /checkout/.
  // The destination must be the actual Woo checkout link, with its real form.
  await expect(page).toHaveURL(checkoutUrl.toString());
  const form = page.locator('form.checkout');
  await expect(form).toBeVisible().catch(async () => {
    const cartResponse = await page.evaluate(async () => {
      const response = await fetch('/?rest_route=/wc/store/v1/cart', { credentials: 'same-origin' });
      const cart = await response.json().catch(() => ({}));
      return { status: response.status, items_count: cart.items_count };
    });
    const text = (await page.locator('body').innerText()).slice(0, 1000);
    throw new Error('Native Woo checkout form not visible. Cart: ' + JSON.stringify(cartResponse) + '; page: ' + text);
  });

  // The fixture deliberately enables BACS (no real payment or live gateway).
  // Submit Woo's native checkout form; never call a private order-creation API.
  await page.locator('#billing_first_name').fill('Test');
  await page.locator('#billing_last_name').fill('Customer');
  await page.locator('#billing_address_1').fill('1 Example Street');
  await page.locator('#billing_city').fill('Testville');
  await page.locator('#billing_postcode').fill('SW1A 1AA');
  await page.locator('#billing_phone').fill('02079460000');
  await page.locator('#billing_email').fill('uc-checkout@example.test');
  await expect(page.locator('input[name="payment_method"][value="bacs"]')).toBeAttached();
  await page.locator('#place_order').click();
  await expect(page).toHaveURL(/order-received/);
  // Modern Woo order-received templates need not use classic overview CSS.
  // Verify shopper-visible confirmation and resolve the order ID from the
  // Woo receipt route, including WordPress plain permalink installations.
  const receipt = new URL(page.url());
  const segments = receipt.pathname.split('/');
  const endpointIndex = segments.indexOf('order-received');
  const orderId = Number(receipt.searchParams.get('order-received') || (endpointIndex >= 0 ? segments[endpointIndex + 1] : 0));
  expect(orderId).toBeGreaterThan(0);
  await expect(page.locator('body')).toContainText('Thank you. Your order has been received.');
  await expect(page.locator('body')).toContainText('Order #: ' + orderId);
  await expect(page.locator('body')).toContainText('Accessible Variable Product');
  await expect(page.locator('body')).toContainText('Blue');

  // Read back the persisted Woo order, not a UC/browser-local snapshot.
  const proof = await page.evaluate(async (id) => {
    const response = await fetch('/?rest_route=/uc-fixture/v1/order-proof&order_id=' + encodeURIComponent(id), {
      credentials: 'same-origin', cache: 'no-store',
    });
    return { status: response.status, body: await response.json() };
  }, orderId);
  expect(proof.status).toBe(200);
  expect(proof.body.id).toBe(orderId);
  expect(proof.body.product_id).toBe(productId);
  expect(proof.body.variation_id).toBe(variationId);
  expect(proof.body.quantity).toBe(1);
  expect(proof.body.payment_method).toBe('bacs');
  expect(proof.body.total_minor).toBe(2200);
});
