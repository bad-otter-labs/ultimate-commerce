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

The committed WordPress.org template is:

`packages/ultimate-commerce-for-woocommerce/languages/ultimate-commerce-for-woocommerce.pot`

It is generated only from the canonical Free package through the official WP-CLI `wp i18n make-pot` command. The dedicated toolchain is pinned by `tools/i18n/composer.lock` to WP-CLI bundle 2.12.0 and is development tooling only.

Generation includes both PHP and JavaScript source. `POT-Creation-Date` is deliberately blank so identical source regenerates byte-identical template content. Development updater overlays, repository docs/tests and the legacy private-distribution bridge do not enter the public template.

The permanent translation-template workflow installs the exact lockfile, runs Composer's locked advisory audit, validates the toolchain licences, regenerates the POT into a temporary path and fails if it differs from the committed template.

## Regression gate

`.github/workflows/translation-readiness.yml` checks:

- canonical text-domain/header metadata
- absence of variable/dynamic PHP translation strings
- extractable translated error literals
- `wp-i18n` dependencies and script-translation registration
- plural-aware cart accessibility copy
- translated variation-controller fallbacks
- presence of the public theme/store integration guide

The source-readiness gate is complemented by `.github/workflows/translation-template.yml`, which proves the committed Phase 4 POT is reproducible with the pinned official extractor.
