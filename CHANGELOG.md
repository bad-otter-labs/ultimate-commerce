# Changelog

## 0.2.0 - Unreleased

- Establish the canonical `ultimate-commerce-for-woocommerce` Free package boundary for WordPress.org.
- Keep the historical `ultimate-commerce` 0.1.x package as a temporary private-distribution migration bridge only.
- Remove the Bad Otter managed updater and managed-release manifest from the canonical Free runtime.
- Adopt GPL-2.0-or-later metadata for the canonical Free package.
- Migrate legacy `ultimate_commerce_*` options to the canonical `uc_*` prefix without deleting rollback data.
- Build the WordPress.org-targeted package deterministically and record the requirement to move untrusted PR validation off the persistent private runner before repository visibility changes.
- Add public Module API v1 metadata and `uc_register_modules` registration for Pro and third-party extensions.
- Resolve module dependencies before boot and expose blocked/disabled module reasons in diagnostics.
- Add a standalone contract regression test covering external registration, dependency ordering, missing dependencies, cycles and disabled dependencies.
- Add versioned UC capabilities and move diagnostics to `uc_view_diagnostics` instead of a broad WooCommerce capability.
- Add reusable authentication, exact-capability, object-ownership and CSRF helpers with fail-closed errors.
- Add a strict UC REST route registrar requiring explicit permission callbacks, typed request arguments and bounded pagination helpers.
- Add Security API v1 foundations for signed guest tokens, encrypted secret storage, SSRF-safe provider requests and signed webhook replay leases.
- Add external-boundary regression tests for token tampering, plaintext-secret prevention, provider allowlists and webhook replay protection.
- Add Audit API v1 with structured high-impact events, safe context guardrails and pluggable sinks without implicit debug-log persistence.
- Add Privacy API v1 with canonical data classifications, retention vocabulary and WordPress exporter/eraser registration for UC-owned personal data.
- Add Execution API v1 contracts for short critical-section locks, durable idempotency claims, replaceable rate limiting and Action Scheduler jobs.
- Add bounded retry policy and regression coverage for lease safety, duplicate business execution, rate-limit decisions and PII-minimised job arguments.
- Add a deterministic WordPress.org package audit, private vulnerability-reporting policy and official Plugin Check CI gate for the built Free artifact.
- Move Ultimate Commerce out of the WooCommerce admin menu into its own top-level Overview/Diagnostics structure with public `uc_admin_menu` and `uc_admin_overview` extension points.
- Add Storefront API v1 with public product-card, bounded catalogue-query and semantic rendering contracts for themes/extensions.
- Add batch-safe variable-product state with text/button, colour/image swatches, selected variation media/price, availability, accessibility semantics and Woo Store API cart handoff metadata.
- Add validated catalogue filter/sort state for Woo product attributes, price and availability with deterministic URL state and public Pro/third-party extension points.
- Keep `uc_product_view_model` and `uc_variation_view_model` as documented compatibility filters while introducing the supported Storefront API service surface.
- Add Cart API v1 with variation-aware quick add and an accessible cart drawer backed exclusively by WooCommerce Store API cart/session/totals truth.
- Add cart drawer extension slots/events, quantity/remove operations, loading/error states and native Woo link/form fallbacks without embedding customer nonces in cacheable markup.
- Add capability-protected module enable/disable management with preserved extension preferences, dependency/runtime status visibility and shared Module API integration.
- Add bounded, schema-versioned settings import/export for merchant module preferences without exporting secrets, runtime metadata or arbitrary options.
- Add explicit uninstall/data-retention controls: retain merchant configuration by default, always clear short-lived UC runtime state/capabilities, and support an allowlisted full purge without touching WooCommerce-owned data.
- Add mature read-only diagnostics with environment/compatibility/API/module health plus a privacy-minimised copyable support snapshot.
- Make current PHP and storefront JavaScript strings translation-ready with extractor-visible literals, WordPress `wp-i18n` script translations/plurals and the canonical `/languages` domain path.
- Document the supported theme/store integration boundary, public Storefront/Cart contracts and UC-disabled WooCommerce fallbacks without promoting internal classes or theme overrides to stable APIs.
- Add Wishlist API v1 with bounded signed-in persistence, browser-local guest state, authenticated merge, Woo Store API-backed display, privacy export/erase and explicit uninstall purge integration.
- Add Recently Viewed API v1 with bounded browser-local product-ID history, cache-safe single-product tracking, live Woo Store API rendering and clear-history controls.
- Add Account/Order API v1 with authenticated account summaries, HPOS-safe Woo order view models, strict customer ownership and public extension hooks.
- Route cross-repository pull requests to GitHub-hosted validation while retaining the controlled runner for trusted same-repository development and publishing.
- Expand the WordPress.org readme with installation, privacy, external-service, source/build and support disclosures, enforced against the release ZIP.

## 0.1.4 - 2026-09-15

- Publish immutable GitHub releases and assets through the GitHub REST API instead of relying on the `gh` CLI being installed on the self-hosted runner.
- Keep Bad Otter draft-candidate validation, exact-package Stable promotion and live WordPress managed-update verification ahead of GitHub release publication.

## 0.1.3

- Finalise the first managed-release workflow after central Bad Otter product registration.
- Fix canonical package validation so the build step verifies the locally resolved package filename rather than referencing a step output before it exists.
- Require draft candidate validation, exact-package Stable promotion and live WordPress update/package integrity verification before creating the immutable GitHub release.

## 0.1.2

- Build deterministic release ZIPs through the canonical Bad Otter WordPress package contract.
- Preserve `entitlement: null` in source while resolving the published package entitlement to `ultimate-commerce-core`.
- Add `free-core-updates` to the published manifest and validate deterministic package output in CI.

## 0.1.1

- Add canonical `module.json` for Bad Otter managed product registration.
- Lock release identity to `ultimate-commerce` / `core` and validate manifest/version alignment in CI.
- Preserve the existing Bad Otter OIDC publish and native WordPress managed-update path.

## 0.1.0

- Bootstrap Ultimate Commerce as an installable WooCommerce plugin.
- Add module registry and initial product, variation, cart, account and returns modules.
- Add presentation-neutral product/variation view models.
- Declare HPOS and Cart/Checkout Blocks compatibility.
- Add WooCommerce diagnostics screen.
- Add Bad Otter managed update client with checksum and package identity verification.
- Add CI validation and Bad Otter release workflow.
