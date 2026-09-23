# Free identity migration: private 0.1.x to WordPress.org Free

Status: **Phase 0 migration contract**

The early/private Ultimate Commerce 0.1.x package predates the final decision to distribute the public Free plugin through WordPress.org. This document defines the transitional repository boundary while the planned `ultimate-commerce-for-woocommerce` WordPress.org identity is prepared and before public directory installs exist.

## Identities

Legacy private package:

```text
Directory: packages/ultimate-commerce
Main file: ultimate-commerce.php
Display name: Ultimate Commerce
Distribution: Bad Otter managed updates
Purpose now: migration bridge only
```

Canonical new Free package:

```text
Directory: packages/ultimate-commerce-for-woocommerce
Main file: ultimate-commerce-for-woocommerce.php
Display name: Ultimate Commerce for WooCommerce
Planned WordPress.org slug: ultimate-commerce-for-woocommerce
Distribution target: WordPress.org
```

The PHP namespace remains `BadOtter\UltimateCommerce` across the transition. The identity change is a plugin package/distribution change, not a reason to break PHP consumers.

## Repository rules during migration

1. New Free product functionality is implemented only in `packages/ultimate-commerce-for-woocommerce`.
2. `packages/ultimate-commerce` is frozen except for migration/security fixes required to move existing 0.1.x private installs safely.
3. The canonical Free package contains no Bad Otter updater, Bad Otter managed-release manifest or third-party `Update URI`.
4. The legacy package may retain its existing Bad Otter updater until the final migration mechanism is proven.
5. Do not publish the canonical Free package through the Bad Otter Stable channel as though it were the long-term public update source.
6. Do not remove the legacy package from the repository until the installed-base migration has been tested on a real 0.1.4 fixture.

## Option migration

Canonical WordPress.org option names use the reviewer-safe `ulticofo_` prefix. The earlier `uc_` canonical names and the private 0.1.4 `ultimate_commerce_*` names are migration-only aliases.

The new Free bootstrap performs an idempotent, non-destructive copy when the canonical value does not already exist:

```text
ultimate_commerce_version        -> ulticofo_version
ultimate_commerce_schema_version -> ulticofo_schema_version
ultimate_commerce_modules        -> ulticofo_modules
uc_version                       -> ulticofo_version
uc_schema_version                -> ulticofo_schema_version
uc_modules                       -> ulticofo_modules
```

Legacy values are intentionally not deleted during Phase 0. This makes rollback and migration verification safer. A later cleanup may remove obsolete keys only after the migration path is established and tested.

## Deterministic 0.1.4 data-upgrade fixture

The repository now carries `tests/free-identity-upgrade-fixture.php` as a blocking regression around the released private 0.1.4 option state.

The fixture proves that:

1. legacy `ultimate_commerce_*` and pre-review `uc_*` options are copied only when the corresponding canonical `ulticofo_*` value is absent;
2. module preferences survive byte-for-byte at the PHP value level;
3. existing canonical values win and are never overwritten by stale legacy state;
4. legacy options remain available for rollback/recovery;
5. rerunning migration performs no additional writes;
6. migrated canonical options are created with autoload disabled;
7. WooCommerce-owned options are not read as migration targets, copied, overwritten or deleted;
8. the canonical Free package has no third-party `Update URI` or legacy updater while the frozen 0.1.4 fixture retains its historical managed-update identity.

This is the pre-release **data migration** fixture. It deliberately does not claim that WordPress can safely switch the active plugin basename before the directory slug and final handoff mechanism exist.

## Installed-plugin migration still to prove

Changing the plugin directory/main file changes the WordPress plugin basename. The final transition for an installed private 0.1.4 copy therefore requires an explicit, tested bridge; source renaming alone is not sufficient.

Before WordPress.org launch, validate a migration fixture that starts with the released 0.1.4 ZIP and proves all of the following:

1. the site reaches the approved WordPress.org package identity without two active copies of the same runtime;
2. module settings survive;
3. no WooCommerce data is copied or damaged;
4. the old Bad Otter update override no longer controls the canonical Free package;
5. future public updates are discovered through WordPress.org;
6. rollback/recovery steps are documented if the transition is interrupted.

The exact bridge mechanism should be selected only after the WordPress.org slug is accepted and tested against real WordPress update behaviour. Do not hard-code an assumption that the planned slug is reserved before acceptance.

## Release workflow boundary

Normal validation for the canonical Free package runs independently from the legacy bridge and must remain suitable for a future public repository.

The existing Bad Otter publication workflow is historical/bridge infrastructure for `packages/ultimate-commerce`; it must not be generalized back into the canonical Free package. Ultimate Commerce Pro will ultimately own the long-term Bad Otter managed-release workflow in its separate private repository.
