# Free development distribution

Status: temporary pre-WordPress.org distribution path.

The canonical Free source remains `packages/ultimate-commerce-for-woocommerce/` and contains **no** Bad Otter updater. Development installations are built by applying an out-of-tree updater overlay at package time.

## Identities

- WordPress plugin slug/root: `ultimate-commerce-for-woocommerce`
- Canonical public product: Ultimate Commerce for WooCommerce
- Temporary Bad Otter development product: `ultimate-commerce-for-woocommerce-dev`
- Development module: `core`
- Entitlement: none
- Expected Bad Otter catalogue access: `free`
- Update endpoint: `/v1/updates/ultimate-commerce-for-woocommerce-dev/core`

Do not reuse the historical `ultimate-commerce` 0.1.x Bad Otter product for this channel. That product is the private-identity migration bridge and must not begin serving canonical 0.2.x installations.

## Build contract

`scripts/build-development-package.py` copies the canonical Free package into a temporary staging directory and only there:

1. changes the plugin/readme version from the canonical base (for example `0.2.0`) to a prerelease such as `0.2.0-dev.1`;
2. adds `Update URI: https://badotter.io/ultimate-commerce-for-woocommerce-dev`;
3. marks the runtime with `ULTIMATE_COMMERCE_DEVELOPMENT_BUILD` and the development product id;
4. injects the two `BadOtter\\UltimateCommerce\\DevelopmentUpdates` classes;
5. adds a development-only `module.json` for Bad Otter release-publisher identity;
6. builds a deterministic ZIP and SHA-256 file.

The builder fails if development markers already exist in canonical Free source.

## Update safety

Development update lookup uses the shared Free `ProviderHttp` SSRF-safe client and the exact `api.badotter.io` authority. Package downloads are HTTPS-only WordPress safe-HTTP requests. Before WordPress installs an update, the updater verifies:

- a valid SHA-256 descriptor;
- the downloaded package checksum;
- canonical archive root `ultimate-commerce-for-woocommerce/`;
- canonical Plugin Name;
- the exact published version.

WooCommerce and WordPress remain authoritative for plugin/runtime behavior; the development updater only owns temporary package delivery.

## Versioning and WordPress.org handoff

Use prerelease versions in increasing order:

```text
0.2.0-dev.1
0.2.0-dev.2
0.2.0-dev.3
...
```

PHP/WordPress `version_compare()` treats final `0.2.0` as newer than the `0.2.0-dev.N` line. The intended last Bad Otter-delivered update is therefore the final updater-free canonical package. Once that package is installed, the Bad Otter development updater no longer exists on the site and future Free updates are owned by WordPress.org.

The exact publication mechanics for that final handoff package must be exercised against Bad Otter and the accepted WordPress.org slug before public launch; do not assume a handoff occurred merely because the package can be built.

## Publishing a development build

The manual `Publish Free Development Build` workflow accepts a version such as `0.2.0-dev.2`. It:

1. builds and validates the deterministic development package;
2. requires Bad Otter registration for `ultimate-commerce-for-woocommerce-dev/core` with `access=free`;
3. obtains short-lived OIDC release-publisher identity;
4. creates and promotes the Bad Otter candidate;
5. verifies the public development update endpoint reports the exact version/checksum;
6. retains the installable ZIP and checksum as a workflow artifact.

The workflow is manual only. Merging ordinary source changes cannot publish a development release automatically.

## WordPress.org boundary

The existing WordPress.org package audit remains authoritative for public Free packages. It rejects Bad Otter API/update markers and private updater classes. Development-distribution code lives under `development/` and is never copied by the WordPress.org package builder.
