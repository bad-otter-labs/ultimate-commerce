# ADR 0003: WordPress.org-safe global identifier prefix

Status: **Accepted**

## Context

The pre-release architecture used `uc_` as the storage and capability prefix. During the WordPress.org review of **Ultimate Commerce for WooCommerce**, the Plugins Team identified that prefix as too short for declarations, globals and stored data and stated that a plugin-specific prefix should be at least four characters and distinctive.

The same review also asked for nonce and permission checks to be clear before request processing. The existing admin handlers enforced both requirements, but nonce verification was hidden behind a shared wrapper that static review tooling could not infer reliably.

## Decision

1. WordPress-global and persistent identifiers owned by Ultimate Commerce Free use `ulticofo_`, matching the reviewer-provided naming pattern for this plugin.
2. This includes option names, transient/runtime prefixes, capabilities, user-meta keys, admin-post/AJAX actions, scheduled-action hooks and nonce action/field names.
3. New code must not write new persistent data or register new WordPress-global actions under `uc_`.
4. Existing `uc_*` and private pre-0.2 `ultimate_commerce_*` data may be read only for migration, rollback compatibility or uninstall cleanup.
5. Admin mutation handlers use native `check_admin_referer()` calls in addition to independent capability checks so WordPress.org tooling can verify the CSRF guard statically.
6. The PHP namespace `BadOtter\\UltimateCommerce`, REST namespace `ultimate-commerce/v1`, public `ultimate_commerce_*` hooks/shortcodes and presentation-only `uc-` CSS/DOM classes are unchanged because they are already distinctive or are not WordPress persistent/global identifiers.

## Consequences

- existing pre-release options migrate idempotently to `ulticofo_*` without overwriting canonical values;
- short-lived legacy locks, idempotency/replay records and rate-limit transients are not reused for new work and are included in cleanup paths;
- legacy wishlist user meta is migrated lazily and remains covered by privacy/uninstall cleanup;
- capability installation is version-bumped so default roles receive the new capability names;
- release checks no longer need a PHPCS nonce-verification exclusion for the admin handlers;
- documentation and tests must treat `ulticofo_` as canonical while retaining explicit legacy fixtures where compatibility is being tested.

## Related documents

- `FOUNDATION.md`
- `BLUEPRINT.md`
- `docs/security-request-conventions.md`
- `docs/free-identity-migration.md`
- `docs/wordpress-org-release-gate.md`
