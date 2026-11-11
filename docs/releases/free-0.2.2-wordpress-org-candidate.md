# Free 0.2.2 WordPress.org release candidate

Status: **development PR only — not approved or published** (11 November 2026)

## Canonical product

- WordPress.org listing: https://wordpress.org/plugins/bad-otter-ultimate-commerce-woocommerce/
- WordPress.org currently advertises 0.2.0 (checked 2026-11-11).
- Public Free source package: `packages/ultimate-commerce-for-woocommerce/`.
- Public plugin slug / ZIP root: `bad-otter-ultimate-commerce-woocommerce`.
- Official source main file remains `ultimate-commerce-for-woocommerce.php`.
- GitHub `v0.1.4` is the older private-update product, **not** the canonical WordPress.org Free release.
- The 0.2.1 source/header was developed after the public 0.2.0 release. This candidate advances to **0.2.2**, without reusing the 0.2.1 development version or overwriting an immutable WordPress.org tag.

The plugin header, runtime `ULTIMATE_COMMERCE_VERSION`, readme Stable tag, changelog and extracted translation template's Project-Id-Version must all agree on `0.2.2`. Do **not** change the WordPress.org basename, text domain, requirements, update mechanism or Pro dependency. Free is standalone and does not include or require Pro, store-specific code or hosted distribution services.

## Release contents

This candidate includes merged Free development from the Storefront API 1.1 line:

- WooCommerce Store API-backed canonical product-search state; Woo continues to own products, filtering, stock, prices, carts and orders.
- Optional semantic, labelled search and taxonomy/attribute/availability/price filter controls, including zero-result recovery.
- Optional native sort, page-size and page navigation, including the WordPress-safe `uc_catalog_page` alias for themes embedding catalogues in pages.
- Existing variation-aware Quick Add, cart drawer and WooCommerce integration, now supported by a disposable real-browser guest checkout/order proof.
- No gift-card, stored-value, new database, paid entitlement, Pro module, remote index or custom product search service.

## Engineering gates before publication

- [x] Live compatibility matrix passed for WordPress 6.6.4/WooCommerce 9.8.5/PHP 8.1, WordPress 7.1.1/WooCommerce 11.1.0/PHP 8.3 and WordPress 7.1.4/WooCommerce 11.2.2/PHP 8.3 (HPOS smoke) on the final source revision.
- [x] All **exact-head** Free PR checks green at `2a25d84c17ffe8130ef13f809bd5ed7a71f301e7`: Full Gate, official WordPress.org Plugin Check and package inspection, translation POT, WPCS/PHPStan/dependency audits, PHP/WooCommerce compatibility, frontend accessibility and browser acceptance. Any subsequent code change requires a new exact-head run.
- [x] Release ZIP built deterministically from the canonical WordPress.org slug and inspected by CI. SHA-256: `99aace35bbfd1db6e30b7e94166766207acc92ffdc3f6f5dce83e00c63dfebf4`. Retained candidate artifact: [CI run 43042096481](https://github.com/bad-otter-labs/ultimate-commerce/actions/runs/43042096481).
- [ ] Confirm the current WordPress.org SVN tag `0.2.2` does not already exist and plugin version metadata matches the requested release.
- [ ] Verify the published **0.2.0** WordPress.org package can upgrade to **0.2.2** in a disposable WordPress fixture or an otherwise controlled pre-launch installation; check plugin basename, existing settings, wishlist retention, Woo flows and recovery. This remains a public-distribution compatibility check even when the merchant site is not launched.
- [ ] On the existing **pre-launch FishingClothing installation** (no separate staging clone requested), take and verify a restorable files/database backup, then validate an authorized upgrade from the installed **0.2.1** to the exact candidate **0.2.2**. Verify account/cart/session, variations, filter GET forms, checkout, HPOS, taxes, shipping and configured gateways; use controlled test data and keep outbound notifications/payments disabled where appropriate. Do not install automatically as part of this PR.
- [ ] Validate the proposed FishingClothing theme integration **on the same pre-launch site** after a verified backup and separately approved theme update, with the Customizer opt-in initially disabled. Inspect canonical/noindex, responsive UI, navigation and screen-reader experience. The disposable browser CI proof is not a substitute for inspecting the merchant installation.
- [ ] Perform a read-only Pro compatibility review to ensure Free's existing public APIs and version checks remain valid. Keep Pro and gift-card code untouched.
- [ ] Confirm the `wordpress-org` GitHub environment and WordPress.org SVN release identity/credentials are appropriately configured; obtain a deliberate single-maintainer publication decision.

## Publication (separate approval)

After review, successful PR CI, the necessary public-0.2.0 upgrade proof and pre-launch merchant acceptance, and an explicit publication decision:

1. Merge the candidate PR into protected Free `main`.
2. Verify all required **post-merge** `main` gates pass for that exact SHA.
3. From the `Publish WordPress.org Release` workflow on Free `main`, explicitly choose version `0.2.2` and enable the publish confirmation.
4. Do not invoke the publisher to test the candidate: this workflow makes a **real SVN trunk + immutable tag commit**.
5. Verify SVN `tags/0.2.2`, the public listing and the canonical ZIP checksum before enabling customer updates.
6. Validate and, when explicitly authorized, upgrade the existing pre-launch FishingClothing site with backups and rollback verified. A separate staging environment is not required for this unlaunched merchant site. Public WordPress.org publication is nevertheless a separate decision affecting other stores.

If any blocking test fails, stop publication; fix the branch and rerun. Do not rewrite an existing SVN version tag, upload an unverified ZIP, change a public slug or silently overwrite the merchant's current Free plugin.

**No WordPress.org publication, live deployment, FishingClothing release, Pro or gift-card changes are included in this candidate PR.**
