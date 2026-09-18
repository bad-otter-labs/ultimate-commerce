# Supported WordPress / WooCommerce compatibility matrix

Ultimate Commerce Free declares compatibility only after a live WordPress/WooCommerce install passes the repository matrix.

## Release matrix

| Purpose | WordPress | PHP | WooCommerce | Woo package SHA-256 |
| --- | --- | --- | --- | --- |
| Declared floor | 6.6.4 | 8.1 | 9.8.5 | `9008e72eacda8f3bda01d0b704502eaddd8bc147651d61bd9aa7a015bf583964` |
| Current stable | 7.1.1 | 8.3 | 11.1.0 | `ab56c02b4e0b0685702408624822d8f598ba8f9a9333bcc3673b5da24e03e4f1` |

WooCommerce 11.1.0 itself requires WordPress 7.0 or newer, so it is not a valid dependency for testing the WordPress 6.6 floor. WooCommerce 9.8.5 is used for the floor case because that release declares WordPress 6.6 support.

The plugin metadata therefore records:

- `Requires at least: 6.6`
- `Requires PHP: 8.1`
- `WC requires at least: 9.8`
- `Tested up to: 7.1`
- `WC tested up to: 11.1`

These are tested support claims, not an assertion that every historical WordPress/WooCommerce cross-product is a valid upstream combination.

## Live test behaviour

`.github/workflows/compatibility-matrix.yml` creates a fresh isolated MariaDB + WordPress installation for each matrix row.

The test:

1. downloads the exact WordPress core version through WP-CLI;
2. downloads the exact WooCommerce GitHub release ZIP and verifies the pinned SHA-256;
3. copies the canonical Free package into the fresh site and activates WooCommerce + Ultimate Commerce;
4. enables HPOS through `wp wc hpos enable` without an incompatibility bypass;
5. asks WooCommerce's `FeaturesUtil` registry to confirm UC declared `custom_order_tables` and `cart_checkout_blocks` compatibility;
6. creates a real Woo product and customer-owned order;
7. confirms the order is physically present in the HPOS `wc_orders` table and reloads it through Woo CRUD;
8. reads the same order through `OrderViewModel::fromOrder()` and `AccountViewModel::forCurrentUser()`;
9. requests the created product through WooCommerce Store API and requires HTTP 200.

The disposable WP-CLI container receives a 512 MB test-only PHP memory limit so current WordPress archives can be extracted. That setting is test infrastructure and does not change Ultimate Commerce runtime requirements.

## Trust and isolation

The permanent workflow uses the repository's trust-aware runner routing:

- same-repository branches and `main` may use the controlled Bad Otter runner;
- cross-repository/fork pull requests are forced to GitHub-hosted `ubuntu-latest`.

Every case uses an isolated Docker network, ephemeral MariaDB container and temporary WordPress filesystem. Cleanup is performed inside a disposable container so host/container file ownership cannot pollute the runner.

## Updating the matrix

A compatibility metadata increase requires a green live matrix first.

When WordPress or WooCommerce current stable changes:

1. verify the upstream minimum-version relationship;
2. select a valid floor/current pair;
3. pin the WooCommerce release ZIP checksum;
4. run the live matrix;
5. update plugin/readme metadata only in the same PR whose matrix proves the claim.

Do not raise `WC tested up to` or WordPress `Tested up to` based only on static analysis or release notes.
