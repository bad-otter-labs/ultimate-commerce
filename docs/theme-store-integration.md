# Theme and store integration

Ultimate Commerce for WooCommerce is an enhancement layer over WooCommerce. **WooCommerce remains authoritative** for products, variations, prices, sellable stock, cart/session state, totals, tax, customers, orders, payments, refunds and native shipping state.

Themes and store integrations should consume the public UC contracts below and keep a normal WooCommerce fallback. Do not require Ultimate Commerce for the store's core commerce data to remain operable.

## Guard public APIs before use

A theme must feature-detect the public API it consumes. For example:

```php
use BadOtter\UltimateCommerce\Storefront\Catalog;

if (defined('ULTIMATE_COMMERCE_STOREFRONT_API_VERSION') && class_exists(Catalog::class)) {
    $result = Catalog::query(array('per_page' => 12));
    // Render the supported UC product-card state or use Catalog::renderCard().
} else {
    // Fall back to the theme's normal WooCommerce loop/templates.
}
```

Do the same for Cart API consumers with `ULTIMATE_COMMERCE_CART_API_VERSION` and `BadOtter\UltimateCommerce\Storefront\CartDrawer`.

Public API constants describe contract compatibility; internal file paths, private methods, option names and storage implementation are not theme APIs.

## Product cards, catalogue and variations

Storefront API v1 is documented in `docs/storefront-catalogue-contract-v1.md`.

Supported theme entry points include:

- `Catalog::query()` for bounded Woo-backed catalogue state
- `Catalog::product()` / `ProductCardViewModel::fromProduct()` for one reusable product-card model
- `Catalog::renderCard()` / `Catalog::renderList()` for the default semantic renderer
- `VariationViewModel::forProduct()` for bounded variable-product state
- `ProductCardRenderer::enqueueAssets()` when a custom renderer adopts UC's documented variation markup/data attributes

Themes own CSS, layout, artwork and placement. UC's default markup is semantic rather than brand styling.

For local/custom variation attributes, use each option's `request_value` when handing the choice back to WooCommerce. The normalized `value` is for UC matching and may differ in case/spacing.

The variation controller emits the bubbling `uc:variation-change` event for presentation integrations. It does not create a second stock or variation truth.

## Merchant-owned catalogue GET integration (native search, filters and sorting)

For a conventional Woo Shop page, a theme can opt into Free's semantic forms without creating a second catalogue query or replacing Woo product/cart truth. This is a **template integration example, not a plugin-wide shortcode or automatic archive hijack**:

```php
<?php
use BadOtter\UltimateCommerce\Storefront\Catalog;

$uc_ready = defined('ULTIMATE_COMMERCE_STOREFRONT_API_VERSION')
    && version_compare((string) ULTIMATE_COMMERCE_STOREFRONT_API_VERSION, '1.1.0', '>=')
    && class_exists(Catalog::class);
$shop_url = function_exists('wc_get_page_permalink') ? wc_get_page_permalink('shop') : '';

if ($uc_ready && is_string($shop_url) && $shop_url !== '') {
    // Use the unfiltered Shop permalink as the destination; do NOT pass
    // $_SERVER['REQUEST_URI'], a return_to field or an arbitrary GET URL.
    $request = isset($_GET) && is_array($_GET) ? wp_unslash($_GET) : array();

    echo Catalog::renderList(
        $request,
        array(
            'pagination_base_url' => $shop_url,
            'show_filter_controls' => true,
            'show_sort_control' => true,
        )
    ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- UC's documented renderer escapes its HTML.
} else {
    // Render this theme's ordinary WooCommerce archive loop and controls.
}
```

On every GET request, Free normalises the supported `search`, `filter_<attribute>`, price, availability, sort, page and per-page fields before delegating the product query to Woo Store API. The search and filter form preserves the chosen sort/page size, then resets to page one. The separate sort form preserves current validated search/facets, then resets to page one. Previous/Next links preserve all canonical state and enforce Free's page limit. Global product/category archive styling is the theme's responsibility, and the theme should not render a duplicate native Woo archive loop beside the UC cards.

The filter form uses the existing registered Woo attribute/taxonomy descriptors and native GET checkboxes, so unchecked facets clear normally. Pro's `recommended` choice appears through the supported sort-definition extension hook only while registered. Only the trusted unfiltered permalink can become a form action; it must pass WordPress same-host validation. When the Free plugin is inactive, the theme's normal Woo loop remains its fallback.

Product cards still use Woo's actual product, variation, price and stock view models and Woo's native add-to-cart/Store API cart boundaries; this does **not** implement checkout or claim a complete merchant journey test. The standalone PHP regression exercises the combined search/filter/sort/page/card rendering. Before enabling this on a live theme, separately check the rendered archive in a real Woo installation for mobile layout, attribute choices, keyboard/screen-reader order, variation selection, quick add, cart drawer, checkout and Woo order creation. No live store is changed by this example.

## Cart drawer and Quick Add

Cart API v1 is documented in `docs/cart-drawer-contract-v1.md`.

The built-in Cart module normally outputs the drawer shell in `wp_footer`. A theme can own placement without forking behavior:

```php
add_filter('ultimate_commerce_cart_drawer_auto_render', '__return_false');
```

Then render `CartDrawer::render()` where appropriate and use `CartDrawer::trigger()` for an accessible opener. If the custom markup adopts the documented contract, `CartDrawer::enqueueAssets()` loads the controller/structural CSS.

Quick Add and cart mutations go directly through WooCommerce Store API. Do not copy the Woo cart into theme local storage, create a UC cart endpoint, recalculate totals, or bypass Woo stock/purchasability errors.

Supported cart extension seams include named `ultimate_commerce_cart_drawer_slot` positions plus browser events such as `uc:cart-open`, `uc:cart-refresh` and `uc:cart-updated`.

## Catalogue filters and swatches

Use documented Storefront API hooks rather than reading UC internals from templates. Examples include:

- `ultimate_commerce_catalog_filter_definitions`
- `ultimate_commerce_catalog_sort_definitions`
- `ultimate_commerce_catalog_cache_context`
- `ultimate_commerce_product_card_view_model`
- `ultimate_commerce_variation_product_state`
- `ultimate_commerce_variation_swatch_data`

If another plugin owns colour/image swatch metadata, adapt it once through `ultimate_commerce_variation_swatch_data`; do not duplicate that provider's storage logic throughout the theme.

Catalogue cacheability is conservative by default. A store should only opt into `ultimate_commerce_catalog_public_cache_safe` after `ultimate_commerce_catalog_cache_context` varies on every market/tax/currency/geolocation dimension required by that installation.

## Wishlist

Wishlist API v1 is documented in `docs/wishlist-contract-v1.md`. Themes may render `WishlistControls::toggle()` or use the `[ultimate_commerce_wishlist]` shortcode, but must keep WooCommerce as product truth. Guest storage contains product IDs only and signed-in mutations target only the current authenticated customer.

## Recently Viewed

Recently Viewed API v1 (`ULTIMATE_COMMERCE_RECENTLY_VIEWED_API_VERSION`) is documented in `docs/recently-viewed-contract-v1.md`. Merchants may place `[ultimate_commerce_recently_viewed]`; the controller stores only bounded Woo product IDs in browser local storage and resolves display data live from WooCommerce Store API. The browser event `uc:recently-viewed-updated` exposes the current ID list to presentation integrations without creating server-side browsing history.

## Account and orders

Account API v1 (`ULTIMATE_COMMERCE_ACCOUNT_API_VERSION`) is documented in `docs/account-order-contract-v1.md`. Themes and extensions may consume `AccountViewModel::forCurrentUser()` and `OrderViewModel::fromOrder()` only for authenticated customer context. Order access is re-authorised server-side on every model build and remains backed by WooCommerce CRUD/query APIs.

Presentation integrations may extend `ultimate_commerce_account_view_model` and `ultimate_commerce_order_view_model`, or consume the corresponding `*_ready` actions emitted on WooCommerce account surfaces. Do not cache these models publicly or infer guest-order ownership.

## Module and admin extensions

Reusable extensions register modules through Module API v1 and `ultimate_commerce_register_modules`; see `docs/module-extension-contract-v1.md`.

Admin extensions attach beneath the standalone Ultimate Commerce menu through `ultimate_commerce_admin_menu` and must enforce their own exact UC capability on every callback; see `docs/admin-menu-extension-contract.md`.

A theme should not register product behavior by reaching into `ModuleRegistry`, options or module implementation classes directly.

## Translation

Theme-owned copy uses the theme's own text domain. Ultimate Commerce runtime copy uses `bad-otter-ultimate-commerce-woocommerce`. Do not hard-code replacements for UC JavaScript status/error strings; WordPress language packs can translate those through the registered `wp-i18n` runtime.

See `docs/translation-readiness.md`.

## UC disabled or removed

When Ultimate Commerce is unavailable:

- product lists fall back to normal WooCommerce loops/templates
- cart links/drawers fall back to the theme's normal WooCommerce cart/checkout UX
- no product, stock, cart, order or payment migration is required
- the theme must not fatal because a UC class or constant is absent

That fallback requirement is part of the product boundary: Ultimate Commerce enhances WooCommerce; it does not become the commerce system of record.
