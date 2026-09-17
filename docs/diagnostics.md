# Diagnostics and support snapshot

Ultimate Commerce exposes read-only system status under **Ultimate Commerce → Diagnostics**. Access requires the dedicated `uc_view_diagnostics` capability.

## System status

The diagnostics screen reports:

- Ultimate Commerce version, schema and development-build state
- all public UC API versions
- WordPress, WooCommerce and PHP versions
- WordPress memory/debug/cron flags and multisite/object-cache state
- declared HPOS and Cart/Checkout Blocks compatibility
- current HPOS runtime state and Action Scheduler availability
- registered/active/disabled/blocked module counts
- sanitized module identity, product/tier, dependencies, runtime state and dependency issue
- the local uninstall data-retention preference

Disabled modules are not treated as errors because they can be an intentional merchant preference. A blocked module causes the overall status to report **Attention needed**.

## Privacy-minimised support snapshot

The same sanitized model is rendered as copyable JSON for support. The snapshot deliberately excludes:

- site/home URLs and hostnames
- filesystem paths and debug-log paths
- licence keys, credentials, encrypted secret-store contents or other secrets
- customer, order, payment or cart data
- request/server data
- module settings schemas, asset paths and arbitrary compatibility payloads
- database credentials, prefixes or raw option values

The JSON is a diagnostic/support aid, not an import format or a stable application API. Settings portability continues to use the separate schema-versioned settings transfer contract.

## Extension modules

Pro and third-party modules already registered through the public Module API appear automatically through their bounded module status metadata. Free does not inspect extension implementation internals to build diagnostics.
