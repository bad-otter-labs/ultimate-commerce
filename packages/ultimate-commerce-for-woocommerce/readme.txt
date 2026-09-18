=== Ultimate Commerce for WooCommerce ===
Contributors: badotterlabs
Tags: woocommerce, ecommerce, wishlist, variations, cart
Requires at least: 6.6
Tested up to: 7.1
Requires PHP: 8.1
Stable tag: 0.2.0
Requires Plugins: woocommerce
WC requires at least: 9.8
WC tested up to: 11.1
License: GPL v2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

A modular WooCommerce experience layer for catalogue, variations, cart, wishlist, recently viewed products and customer account foundations.

== Description ==

Ultimate Commerce for WooCommerce enhances WooCommerce without replacing it. WooCommerce remains authoritative for products, variations, prices, sellable stock, carts, totals, tax, customers, orders, payments and refunds.

The Free plugin provides reusable storefront and merchant foundations including:

* product-card and catalogue presentation contracts
* variation controls and swatch-ready presentation state
* WooCommerce Store API-backed cart drawer and Quick Add
* signed-in and guest wishlist support
* browser-local recently viewed products
* authenticated account and order presentation contracts
* module management, settings transfer and diagnostics
* privacy, audit and safe execution foundations for extensions

Ultimate Commerce Free is designed to be useful without Ultimate Commerce Pro, a Bad Otter account, a proprietary theme or a hosted Bad Otter service. Paid Ultimate Commerce Pro modules are distributed separately and consume documented public contracts exposed by Free.

The plugin follows a simple boundary: WooCommerce owns commerce truth; Ultimate Commerce enhances the experience around it.

== Installation ==

1. Install and activate WooCommerce.
2. Install and activate Ultimate Commerce for WooCommerce.
3. Open **Ultimate Commerce > Modules** to review the available Free modules.
4. Open **Ultimate Commerce > Settings** for portable module settings and uninstall/data-retention preferences.
5. Use **Ultimate Commerce > Diagnostics** when checking environment, compatibility and module status.

Themes can use the plugin's documented PHP/hooks contracts while retaining their normal WooCommerce fallback.

Optional shortcodes include:

* `[ultimate_commerce_wishlist]` for the customer wishlist.
* `[ultimate_commerce_recently_viewed]` for browser-local recently viewed products.

== Frequently Asked Questions ==

= Does Ultimate Commerce replace WooCommerce? =

No. WooCommerce remains the system of record for products, variations, stock, carts, totals, customers, orders, payments and refunds. Ultimate Commerce reads and enhances WooCommerce state through supported APIs.

= Does the Free plugin require Ultimate Commerce Pro or a Bad Otter account? =

No. Free is intended to be independently useful. Pro is a separate optional product that extends the public contracts provided by Free.

= Does the plugin send my store or customer data to Bad Otter? =

No. Ultimate Commerce Free does not send usage telemetry and does not contact any third-party service by default. Its current storefront network requests are same-origin WordPress/WooCommerce requests.

= What happens if Ultimate Commerce is disabled? =

WooCommerce product, cart, customer and order truth remains in WooCommerce. Themes and integrations should keep normal WooCommerce fallbacks rather than depending on internal Ultimate Commerce implementation files.

= Where is the source code and technical documentation? =

The public source, architecture documents and release tooling are maintained at https://github.com/bad-otter-labs/ultimate-commerce. WordPress.org users should use the plugin listing's support forum for installation support once the listing is live.

== Privacy ==

Ultimate Commerce Free does not send usage telemetry.

Signed-in wishlists store a bounded list of WooCommerce product IDs in WordPress user meta under an Ultimate Commerce-owned key. That data integrates with the WordPress personal-data exporter and eraser.

Guest wishlists store only bounded WooCommerce product IDs in browser local storage. When a customer signs in, those IDs can be merged into the signed-in wishlist and the guest browser entry is cleared.

Recently viewed products are browser-local and store only a bounded list of WooCommerce product IDs. Product display data is resolved live from the store's own WooCommerce Store API rather than storing a second product snapshot.

Account and order presentation reads WooCommerce customer/order objects at request time and does not create a duplicate Ultimate Commerce order database.

Merchant configuration is retained by default on uninstall. An explicit merchant setting can request a bounded purge of Ultimate Commerce-owned configuration and signed-in wishlist data. Browser local storage remains under the visitor's browser controls and the feature UI provides clear actions where applicable.

== External services ==

Ultimate Commerce Free does not contact any third-party service by default and does not require a Bad Otter service to operate.

The cart drawer, product display, wishlist and recently viewed features communicate only with REST/Store API endpoints on the same WordPress/WooCommerce site.

The package includes a defensive provider-HTTP utility for extensions that deliberately integrate an external provider. Free does not configure or invoke a third-party provider through that utility by default. Any extension that adds such a service is responsible for its own clear disclosure, permissions and provider terms/privacy information.

== Source and development ==

The distributed PHP, JavaScript and CSS are human-readable source. The Free package does not depend on a remote executable service and does not ship minified-only source that requires a private build system to understand.

Public source and technical documentation:
https://github.com/bad-otter-labs/ultimate-commerce

The deterministic WordPress.org release ZIP is built from the canonical Free source with:

`python3 scripts/build-wordpress-org-package.py --source packages/ultimate-commerce-for-woocommerce --output-dir dist-wporg`

The resulting ZIP is audited by `scripts/check-wordpress-org-package.py` and by the official WordPress Plugin Check workflow before release.

== Changelog ==

= 0.2.0 =
* Establish the canonical WordPress.org Free package identity and remove the private Bad Otter updater from the Free runtime.
* Add Storefront API v1 product-card, catalogue, filter, variation and swatch-ready presentation contracts.
* Add WooCommerce Store API-backed cart drawer and variation-aware Quick Add.
* Add Wishlist API v1 with signed-in persistence, browser-local guest state, authenticated merge and WordPress privacy export/erase support.
* Add browser-local Recently Viewed API v1 with live WooCommerce product rendering.
* Add Account/Order API v1 with authenticated, HPOS-safe WooCommerce order presentation and strict customer ownership.
* Add merchant module management, bounded settings import/export, uninstall retention controls and privacy-minimised diagnostics.
* Add translation-ready PHP/JavaScript, public theme/store integration documentation and deterministic WordPress.org package validation.
