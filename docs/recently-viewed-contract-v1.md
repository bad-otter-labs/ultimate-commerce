# Recently Viewed contract v1

Ultimate Commerce Free provides a browser-local recently viewed list without creating a server-side browsing-history profile.

## Ownership boundary

WooCommerce remains authoritative for products, publication state, names, imagery, prices, stock and purchasability. Ultimate Commerce stores only a bounded recency-ordered list of WooCommerce product IDs in the visitor's browser.

The public contract version is `ULTIMATE_COMMERCE_RECENTLY_VIEWED_API_VERSION = 1.0.0`.

## Storage and privacy

Recently viewed state uses `localStorage` only. Single-site installs use `uc_recently_viewed_v1`; multisite installs suffix the current blog ID so stores on the same origin do not share history.

The list contains at most 12 unique positive product IDs. Product snapshots, customer identifiers, account IDs, REST nonces, prices and stock are never persisted in the history.

No recently viewed state is written to WordPress options, user meta, cookies, UC REST routes or a hosted service. Because the history lives only in the browser, it is not part of the server-side WordPress personal-data exporter/eraser. Visitors can clear the list with the rendered Clear control or their browser's site-data controls.

## Tracking

When the module is enabled, viewing a WooCommerce single-product page moves that public Woo product ID to the front of the browser-local history. The current product is omitted from rendered recently viewed lists on that page.

The product-page inline configuration contains public page/site data only: current Woo product ID, Woo Store API URL, item bound and site-scoped storage key. It contains no customer/session state and is safe for public page caches.

## Rendering

Merchants can place:

`[ultimate_commerce_recently_viewed]`

The optional `limit` attribute is bounded from 1 to 12; the default is 8.

The browser controller resolves current product name, link and imagery from WooCommerce Store API at render time and preserves the browser recency order. Stale/deleted IDs simply do not render.

The browser emits `uc:recently-viewed-updated` with the current bounded `product_ids` after history changes.

## Module boundary

The Free module key is `recently_viewed`. Disabling it stops tracking/rendering new UC recently viewed UI without changing WooCommerce data.
