# Translation readiness

Ultimate Commerce for WooCommerce uses the WordPress.org text domain `ultimate-commerce-for-woocommerce` for canonical Free runtime strings.

## PHP strings

User-facing PHP strings must be passed to a WordPress translation function with a **literal source string** and the canonical text domain. Do not pass a variable as the first argument to `__()`, `_e()`, `esc_html__()`, `esc_attr__()` or related helpers: standard WordPress extraction tooling cannot discover those strings.

Error helpers may accept an already translated message, but the translation call belongs at the literal call site so the source remains extractable.

## JavaScript strings

Storefront scripts that produce user-facing text depend on WordPress's `wp-i18n` script and register translations with `wp_set_script_translations()`.

- `ultimate-commerce-cart-drawer` uses `__()`, `_n()` and `sprintf()` for loading/mutation/error/accessibility text.
- `ultimate-commerce-variation-controls` uses `__()` for its fallback action labels; normal labels are also supplied from translated PHP state.
- `ultimate-commerce-wishlist` uses `__()`, `_n()` and `sprintf()` for saved-state, error and accessibility copy.
- `ultimate-commerce-recently-viewed` uses `__()`, `_n()` and `sprintf()` for empty/loading/count/clear copy.

Do not introduce a parallel JSON/localization dictionary for translatable copy. Use the WordPress i18n runtime so WordPress.org language packs can supply JavaScript translations.

## Text-domain loading

The canonical plugin header declares:

```text
Text Domain: ultimate-commerce-for-woocommerce
Domain Path: /languages
```

The plugin targets modern WordPress and relies on WordPress just-in-time text-domain loading rather than forcing an early `load_plugin_textdomain()` call.

## Translation template

Phase 3 makes the source extractable and runtime translation-aware. The committed WordPress.org translation template/POT is a Phase 4 submission artifact, generated from the canonical `packages/ultimate-commerce-for-woocommerce/` source only. Development updater overlays and the legacy private-distribution bridge must not enter the public template.

## Regression gate

`.github/workflows/translation-readiness.yml` checks:

- canonical text-domain/header metadata
- absence of variable/dynamic PHP translation strings
- extractable translated error literals
- `wp-i18n` dependencies and script-translation registration
- plural-aware cart accessibility copy
- translated variation-controller fallbacks
- presence of the public theme/store integration guide

This is a source-readiness gate, not a substitute for the Phase 4 POT/template and WordPress.org translation-package review.
