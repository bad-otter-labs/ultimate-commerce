# Account and order contract v1

Ultimate Commerce Free exposes a presentation-neutral customer account and order contract so themes, Pro and third-party extensions can enhance WooCommerce account surfaces without cloning WooCommerce order truth.

## Ownership boundary

WooCommerce remains authoritative for customer identities, orders, line items, totals, payments, refunds and order status. Ultimate Commerce reads those concepts through WooCommerce public CRUD/query APIs and produces presentation view models only.

The public contract version is `ULTIMATE_COMMERCE_ACCOUNT_API_VERSION = 1.0.0`.

Order code must remain HPOS-safe. The contract must not read order truth directly from `wp_posts`, `wp_postmeta`, `get_post_meta()` or custom SQL.

## Account view model

`BadOtter\UltimateCommerce\Account\AccountViewModel::forCurrentUser()` returns `uc.account.v1` for the authenticated WordPress/WooCommerce customer.

The model contains a bounded recent-order list, account/order URLs, stable presentation slots and minimal customer presentation data. It intentionally does not expose email addresses, passwords, payment credentials, order keys, REST nonces or arbitrary user meta.

Recent orders are queried with `wc_get_orders()` scoped to the current customer. The v1 maximum is 20 orders.

Extensions may use:

- `uc_account_recent_order_limit` to adjust the recent-order query within the hard maximum
- `uc_account_view_model` to add presentation data without replacing WooCommerce truth
- `uc_account_view_model_ready` when WooCommerce renders the account dashboard

## Order view model

`BadOtter\UltimateCommerce\Account\OrderViewModel::fromOrder()` accepts a Woo order object or order ID and returns `uc.order.v1` only after server-side ownership verification against the current authenticated actor.

The model contains order identity, status, created time, currency, total, line-item presentation data and stable extension slots. It does not expose the Woo order key.

`uc_order_view_model` may extend the model after ownership has been verified. `uc_order_view_model_ready` fires before WooCommerce renders the order table.

## Authorisation

UI visibility is never authorisation.

Every customer-facing order model verifies that the current actor owns the Woo order through the shared Ultimate Commerce ownership helper. A different signed-in customer receives a forbidden error.

Guest orders deliberately fail closed in API v1. Associating a guest order with a later account requires a separate verified workflow and must not be inferred from a URL, email field or client-supplied order identifier.

## Cache and privacy

Account and order models are private customer context and explicitly report `public_cache_safe: false`. They must never be embedded in publicly cacheable catalogue markup.

Ultimate Commerce stores no duplicate account or order records for this contract. Removing Ultimate Commerce therefore does not affect WooCommerce customer/order truth.

## Free / Pro boundary

Free owns this baseline public contract. Pro customer-portal, tracking, returns, exchanges, stored value and profile modules may extend these slots and filters, but they must not replace the ownership check or create a second order system of record.
