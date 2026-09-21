# Cart Drawer Contract v1

Status: **Phase 2 Free storefront contract**

Ultimate Commerce Free provides a progressive quick-add/cart-drawer layer over WooCommerce Store API. WooCommerce remains authoritative for the cart session, cart item validation, quantities, stock, coupons, shipping and totals. UC owns only the presentation shell, client interaction state and public extension contract.

## Public API marker

```php
ULTIMATE_COMMERCE_CART_API_VERSION === '1.1.0'
```

Public class:

```php
BadOtter\UltimateCommerce\Storefront\CartDrawer
```

Useful methods:

- `CartDrawer::render(array $context = array()): string` — render the drawer shell once.
- `CartDrawer::trigger(array $args = array()): string` — render an accessible open/close trigger for a theme.
- `CartDrawer::enqueueAssets(): void` — enqueue the progressive controller and minimal structural CSS when a custom theme renders compatible markup itself.

The built-in Free Cart module auto-renders the drawer in `wp_footer`. Themes may disable that behavior:

```php
add_filter('ultimate_commerce_cart_drawer_auto_render', '__return_false');
```

and place `CartDrawer::render()` themselves.

## WooCommerce owns cart truth

The client uses the Woo Store API directly:

```text
GET  /wc/store/v1/cart
POST /wc/store/v1/cart/add-item
POST /wc/store/v1/cart/update-item
POST /wc/store/v1/cart/remove-item
```

The first `GET /cart` establishes current Woo cart state and returns the Store API `Nonce` header. Subsequent mutations send that nonce back to Woo. UC stores the nonce only in JavaScript memory for the current page; it is never embedded into cacheable product-card or cart-drawer HTML.

If Woo does not return a nonce but does return a `Cart-Token`, the controller may use the cart token as the Store API fallback. UC does not create a parallel cart session, custom cart table, custom monetary total or custom stock truth.

## Quick Add

The controller progressively enhances the semantic product-card contract from Storefront API v1.

### Simple products

An enabled product-card link with:

```text
data-uc-action="add_to_cart"
```

is sent to Woo Store API using the product ID already present on the parent `data-uc-product-card` element. On a network-level Store API failure, the controller navigates to the original Woo add-to-cart URL so the native fallback remains usable.

### Variable products

The existing `data-uc-variation-form` form remains a normal Woo POST fallback. When a complete purchasable variation is selected, the cart controller sends:

- selected Woo variation ID
- quantity
- global attribute names/slugs such as `pa_color` + `blue`
- case-sensitive local attribute names and original values such as `Logo` + `Yes`

The Storefront API keeps UC's normalized matching input separate from the named native Woo request input. Quick Add deliberately reads `data-uc-native-attribute-input`, not the normalized `data-uc-attribute-input`, so local/custom values preserve their original case and spacing exactly as Woo's Store API expects.

The request goes through `/cart/add-item`. Woo performs final purchasability, stock and cart validation. On a network-level failure the native form is submitted instead.

API/validation errors from Woo are shown in the drawer rather than silently bypassed through the fallback.

## Drawer behavior

The default drawer provides:

- current Woo cart items
- variation labels
- Woo quantity limits
- update quantity
- remove item
- Woo total display
- normal cart and checkout links
- loading and error states
- focus return to the opener
- Escape close
- modal focus trapping
- accessible status/error regions

The controller does not persist a cart snapshot in `localStorage` or another UC store. A page refresh always rehydrates from Woo.

## Public extension points

Filters:

- `ultimate_commerce_cart_drawer_auto_render` — enable/disable Free's `wp_footer` shell.
- `ultimate_commerce_cart_drawer_context` — add presentation-neutral context passed to PHP slots.

Actions:

- `ultimate_commerce_cart_drawer_render_before`
- `ultimate_commerce_cart_drawer_render_after`
- `ultimate_commerce_cart_drawer_slot`

Named PHP slots:

- `before_items`
- `after_items`
- `before_totals`
- `after_totals`
- `before_footer`
- `after_footer`

Browser events:

- dispatch `uc:cart-open` on `document` to open the drawer
- dispatch `uc:cart-refresh` on `document` to rehydrate from Woo
- `uc:cart-opened`
- `uc:cart-closed`
- `uc:cart-state` with `{ cart }` after every rendered Woo cart state, including the initial Store API load and later mutations
- `uc:cart-updated` with `{ cart }` after a successful cart mutation
- `uc:cart-error` with the safe client error shape

These are the supported seams for later Pro conversion modules such as delivery-progress, recommendations and incentive messaging. Consumers that need current cart values should use `uc:cart-state`; they must not issue a duplicate cart request merely to discover totals. Pro should extend slots/events rather than fork cart truth.

## Theme example

```php
use BadOtter\UltimateCommerce\Storefront\CartDrawer;

if (defined('ULTIMATE_COMMERCE_CART_API_VERSION') && class_exists(CartDrawer::class)) {
    echo CartDrawer::trigger(array('label' => __('Bag', 'your-theme')));
}
```

If UC is disabled, the theme should fall back to its normal Woo cart link. No cart/order data is UC-only, so disabling UC does not require a data migration.

## Cache and security rules

- no Store API nonce in product-card/drawer HTML
- no customer identity in public markup
- no UC cart session identifier
- mutation errors remain Woo errors and are not converted into successful UI state
- all remote mutation URLs are same-origin Woo Store API URLs derived from `rest_url()`
- the browser sends same-origin credentials so Woo's normal customer session remains authoritative

## Pro boundary

Free exposes the shell/state contract. Pro may add conversion UI through documented slots/events, but must not replace Woo cart totals, stock validation or session ownership.
