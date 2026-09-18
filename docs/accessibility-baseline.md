# Storefront accessibility baseline

Status: **Phase 4 blocking release evidence**

Ultimate Commerce Free runs a browser-level accessibility baseline against a real WordPress/WooCommerce site rather than relying only on static markup inspection.

## Tested environment and surfaces

The harness in `scripts/run-accessibility-baseline.sh` creates a disposable WordPress 7.1.1 / WooCommerce 11.1.0 site on PHP 8.3 with MariaDB 11.4, then renders:

- a UC variable product card and its radio-style variation controls;
- the UC cart trigger and modal cart drawer;
- the guest Wishlist toggle/list;
- the Recently Viewed list and clear control.

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

A green axe run is an automated baseline, not a claim of complete WCAG conformance or a substitute for human usability testing.

## Defects caught by the baseline

The first live browser runs found and fixed real Free runtime issues:

1. product media links could be focusable with no accessible name when a product had no image;
2. the auto-rendered cart drawer enqueued its controller too late in `wp_footer`, leaving rendered markup without the interaction script;
3. cart initial focus targeted the non-focusable overlay instead of the Close button;
4. Wishlist/Recently Viewed Store API query construction broke when `rest_url()` used plain-permalink `?rest_route=` URLs;
5. variation radio buttons lacked a roving `tabindex`.

These are now regression-protected by the browser baseline plus the existing PHP contract tests.

## Trust and package boundary

`.github/workflows/accessibility.yml` uses the same trust-aware runner routing as the other Free pull-request gates. Fork/cross-repository pull requests cannot fall back to the persistent Bad Otter runner.

Playwright, axe, the fixture MU plugin and browser-test tooling are repository development assets only. They are not copied into the canonical WordPress.org plugin ZIP.
