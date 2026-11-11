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

- [x] Live compatibility matrix passed for WordPress 6.6.4/WooCommerce 9.8.5/PHP 8.1, WordPress 7.1.1/WooCommerce 11.1.0/PHP 8.3 and WordPress 7.1.4/WooCommerce 11.2.2/PHP 8.3 (HPOS smoke); rerun after final metadata edits.
- [ ] All **exact-head** Free PR checks green: Full Gate, official WordPress.org Plugin Check and package inspection, translation POT, WPCS/PHPStan/dependency audits, PHP/WooCommerce compatibility, frontend accessibility and browser acceptance.
- [ ] Verify the release ZIP is deterministic, uses the canonical WordPress.org slug and only contains expected Free package files. Review the checksum and retained CI candidate artifact.
- [ ] Confirm the current WordPress.org SVN tag `0.2.2` does not already exist and plugin version metadata matches the requested release.
- [ ] Stage an upgrade from the actual published **0.2.0** WordPress.org package to **0.2.2** on a disposable/staging WordPress instance, verifying plugin basename, saved settings, wishlist retention, basic Woo flows and rollback.
- [ ] Separately stage an upgrade from the connected merchant's already-installed **0.2.1** build without replacing the live plugin. Verify account/cart/session, variations, filter GET forms, cart-to-checkout, HPOS, sales tax, shipping and checkout gateway paths appropriate to the merchant.
- [ ] Run the proposed FishingClothing theme integration on staging with its Customizer opt-in (off by default). Inspect canonical/noindex policy, responsive UI and screen-reader experience; do **not** claim the detached browser fixture is a merchant staging acceptance.
- [ ] Perform a read-only Pro compatibility review to ensure Free's existing public APIs and version checks remain valid. Keep Pro and gift-card code untouched.
- [ ] Confirm the `wordpress-org` GitHub environment and WordPress.org SVN release identity/credentials are appropriately configured; obtain a deliberate single-maintainer publication decision.

## Publication (separate approval)

After review, successful PR CI, staged upgrade proof, and an explicit publication decision:

1. Merge the candidate PR into protected Free `main`.
2. Verify all required **post-merge** `main` gates pass for that exact SHA.
3. From the `Publish WordPress.org Release` workflow on Free `main`, explicitly choose version `0.2.2` and enable the publish confirmation.
4. Do not invoke the publisher to test the candidate: this workflow makes a **real SVN trunk + immutable tag commit**.
5. Verify SVN `tags/0.2.2`, the public listing and the canonical ZIP checksum before enabling customer updates.
6. Upgrade a controlled staging site first; only later approve any customer/production updates and the separate FishingClothing Shop theme release.

If any blocking test fails, stop publication; fix the branch and rerun. Do not rewrite an existing SVN version tag, upload an unverified ZIP, change a public slug or silently overwrite the merchant's current Free plugin.

**No WordPress.org publication, live deployment, FishingClothing release, Pro or gift-card changes are included in this candidate PR.**
