# Changelog

## 0.2.0 - Unreleased

- Establish the canonical `ultimate-commerce-for-woocommerce` Free package boundary for WordPress.org.
- Keep the historical `ultimate-commerce` 0.1.x package as a temporary private-distribution migration bridge only.
- Remove the Bad Otter managed updater and managed-release manifest from the canonical Free runtime.
- Adopt GPL-2.0-or-later metadata for the canonical Free package.
- Migrate legacy `ultimate_commerce_*` options to the canonical `uc_*` prefix without deleting rollback data.
- Validate normal pull requests on GitHub-hosted runners and build the WordPress.org-targeted package deterministically.

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
