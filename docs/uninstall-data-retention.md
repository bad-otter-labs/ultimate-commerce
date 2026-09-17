# Uninstall and data retention

Ultimate Commerce for WooCommerce keeps merchant configuration by default when the plugin is deleted. This makes reinstall/rollback recoverable and avoids treating plugin deletion as implicit consent to erase durable configuration.

## Default behaviour

Deletion always removes Free runtime metadata and short-lived infrastructure state:

- `uc_version` / `uc_schema_version`
- UC role capabilities and the capability-version marker
- lock records under `uc_lock_*`
- idempotency records under `uc_idem_*`
- replay-protection records under `uc_replay_*`
- rate-limit transients under `uc_rl_*`

Stored module preferences and the encrypted UC secret store are retained by default.

## Explicit purge

A merchant with `uc_manage_settings` can enable **Ultimate Commerce → Settings → Delete Ultimate Commerce data when the plugin is deleted**.

When enabled, uninstall also removes:

- canonical module preferences (`uc_modules`)
- the encrypted UC secret store (`uc_secret_store_v1`)
- the uninstall preference itself
- retained legacy `ultimate_commerce_*` migration options

The purge is allowlisted. It does not delete WooCommerce products, variations, stock, carts, orders, payments, refunds, customers, shipping data, or arbitrary third-party options/tables.

The purge preference is intentionally site-local and is not included in Ultimate Commerce settings export/import.

## Multisite

When WordPress provides multisite site enumeration, uninstall applies each site's own retention preference while switching through sites. Capabilities and runtime state are cleaned per site.

## Extension boundary

Free does not know about Pro implementation details. Extensions must keep their own durable data lifecycle documented. Data placed into Free-owned runtime stores is treated as platform runtime state and may be cleared when Free is uninstalled.
