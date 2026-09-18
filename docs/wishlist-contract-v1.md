# Wishlist contract v1

Ultimate Commerce Free provides a basic wishlist without becoming a second product catalogue or stock system.

## Ownership boundary

WooCommerce remains authoritative for products, publication status, prices, stock and purchasability. Ultimate Commerce persists only product IDs as customer preference state.

The public contract version is `ULTIMATE_COMMERCE_WISHLIST_API_VERSION = 1.0.0`.

## Signed-in customers

Signed-in wishlists are stored as a bounded list of WooCommerce product IDs in UC-owned user meta. The REST surface is under `ultimate-commerce/v1`:

- `GET /wishlist` returns the current customer's IDs.
- `POST /wishlist/items` adds one published Woo product.
- `DELETE /wishlist/items/{product_id}` removes one product.
- `POST /wishlist/merge` merges bounded browser-local IDs after sign-in.

Routes never accept a user ID. Permission is always the current authenticated WordPress user, so another customer's wishlist cannot be addressed through the API.

Frontend catalogue/product HTML stays public-cache-safe: login state, account wishlist IDs and the WordPress REST nonce are never embedded in product-card markup or inline configuration. After page load, a same-origin `admin-ajax.php` bootstrap uses the existing WordPress session cookie to return the current login state, account IDs and a REST nonce with `no-store` response headers. Guests receive no nonce and continue locally.

## Guests

Guests use a browser `localStorage` key containing product IDs only. Single-site installs use `uc_wishlist_v1`; multisite installs suffix the current blog ID so subdirectory stores on the same origin cannot share guest wishlist state. Product snapshots, prices and stock are never stored there.

When a customer is authenticated, guest IDs are merged into the server wishlist and the local guest key is cleared after a successful merge.

## Rendering

Product-card toggles attach through the existing `uc_product_card_slot` / `after_title` extension seam. Product pages also receive a semantic toggle.

Merchants can create a normal WordPress page containing:

`[ultimate_commerce_wishlist]`

The list controller resolves current product name/link/image data from WooCommerce Store API at render time. This keeps WooCommerce as product truth and means saved IDs naturally reflect current catalogue state.

The browser emits `uc:wishlist-updated` with `product_ids` after local state changes.

## Privacy and retention

Signed-in wishlist IDs are registered with the Ultimate Commerce Privacy API using account-lifetime retention and participate in WordPress personal-data export/erase.

The existing uninstall preference applies:

- normal uninstall retains customer wishlist user meta alongside retained UC merchant data;
- explicit full purge removes the current site's wishlist user-meta key;
- WooCommerce product/customer/order records are never deleted.

Guest local storage lives only in the visitor's browser and is not associated with server-side personal data until a signed-in merge succeeds.

## Limits

A wishlist contains at most 100 unique product IDs. Invalid, unpublished or stale Woo product IDs are not accepted into server storage.
