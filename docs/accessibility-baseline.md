# Storefront accessibility baseline

Status: **Phase 4 blocking release evidence**

Ultimate Commerce Free runs a browser-level accessibility baseline against a real WordPress/WooCommerce site rather than relying only on static markup inspection.

## Tested environment and surfaces

The harness in `scripts/run-accessibility-baseline.sh` creates a disposable WordPress 7.1.1 / WooCommerce 11.1.0 site on PHP 8.3 with MariaDB 11.4, then renders:

- a UC variable product card and its radio-style variation controls;
- the UC cart trigger and modal cart drawer;
- the guest Wishlist toggle/list;
- the Recently Viewed list and clear control.
- a second disposable Woo Shop-style page containing three real Woo simple products in two native Woo `product_cat` terms, the opt-in Free search/facet form, Pro-aware sort dropdown and bounded pagination.

The browser side is pinned by `tools/accessibility/package-lock.json` to Playwright 1.63.0 and `@axe-core/playwright` 4.13.0. The matching browser image is `mcr.microsoft.com/playwright:v1.63.0-noble`.

CI requires the committed lockfile, runs `npm audit --audit-level=high`, validates the exact tool versions/licences, and does not fall back to an unlocked `npm install`.

## Automated WCAG baseline

`tools/accessibility/tests/uc-storefront.spec.mjs` runs axe rules tagged for WCAG 2.0/2.1/2.2 A and AA against UC-owned storefront regions. The scan is scoped to UC surfaces so unrelated default-theme markup does not create false failures or hide UC regressions.

The browser tests also verify behavior automated rule engines do not fully cover:

- variation Arrow keys move focus and update `aria-checked`;
- radio-style variation controls use a single roving tab stop;
- disabled options do not enter the radio tab sequence;
- opening the cart moves focus to the actual Close control;
- initial focus is reasserted after the asynchronous Store API refresh if focus escapes the open drawer;
- Tab and Shift+Tab wrap inside the modal drawer;
- Escape closes the drawer and restores focus to the invoking trigger;
- Wishlist toggles update `aria-pressed` and their accessible label;
- Recently Viewed dynamic content and clear controls work from browser-local state.
- a real browser submits a native search, selects the Woo product-category facet, follows a bounded page link, changes sort and minimum price, and selects a product;
- the product's native add-to-cart action changes the **WooCommerce guest cart**, verified through Woo's public Store API rather than any Ultimate Commerce-owned cart state;
- axe scans the whole UC catalogue fixture region, including its labelled native forms and product cards.

This browser journey validates the combined **search → Woo category filter → sort → pagination → product → Woo cart** contract using real Woo CRUD products, native product categories, actual GET submissions and the guest cart. It intentionally does **not** claim a complete merchant checkout/payment/order acceptance, a live-store rollout, or a validated Pro/Gift Card integration. Those remain separate gates.

A green axe run is an automated baseline, not a claim of complete WCAG conformance or a substitute for human usability testing.

## Defects caught by the baseline

The first live browser runs found and fixed real Free runtime issues:

1. product media links could be focusable with no accessible name when a product had no image;
2. the auto-rendered cart drawer enqueued its controller too late in `wp_footer`, leaving rendered markup without the interaction script;
3. cart initial focus targeted the non-focusable overlay instead of the Close button;
4. Wishlist/Recently Viewed Store API query construction broke when `rest_url()` used plain-permalink `?rest_route=` URLs;
5. variation radio buttons lacked a roving `tabindex`.
6. the first real WordPress Page-template catalogue test exposed a conflict between UC's existing `page=2` link and WordPress's own multi-page post query variable; WordPress redirected away the requested catalogue page. The backward-compatible, opt-in `uc_catalog_page` query key now avoids that collision, translating to the same bounded internal Free `page` state before Woo's Store API query.

These are now regression-protected by the browser baseline plus the existing PHP contract tests.

## Trust and package boundary

`.github/workflows/accessibility.yml` uses the same trust-aware runner routing as the other Free pull-request gates. Fork/cross-repository pull requests cannot fall back to the persistent Bad Otter runner.

Playwright, axe, the fixture MU plugin and browser-test tooling are repository development assets only. They are not copied into the canonical WordPress.org plugin ZIP.


## Disposable variable-product to order acceptance (proposed)

The separate `uc-checkout-acceptance.spec.mjs` browser test validates Free's real variation selection, accessible mobile keyboard controls, Store API Quick Add, cart drawer, checkout handoff and a WooCommerce-persisted order. The test uses a throwaway local WordPress/WooCommerce installation and Woo's offline bank-transfer method; it never processes a payment, deploys to a merchant site or changes the Pro/Gift Card code.

The test-specific MU plugin exposes a narrowly gated `uc-fixture/v1/order-proof` GET endpoint only when the explicit `UC_A11Y_CHECKOUT_TEST` test constant is enabled. It returns limited item/total metadata for a fabricated test customer. This endpoint is **not part of the Free distributable ZIP** and must never be installed on a live WordPress site. Full merchant-theme/manual checkout, screen-reader and payment-method acceptance remain separate release gates.
