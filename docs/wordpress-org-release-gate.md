# WordPress.org release gate

Status: **Phase 1 release-engineering baseline**

The canonical Free product is built from:

```text
packages/ultimate-commerce-for-woocommerce/
```

The release target is WordPress.org. The private Bad Otter updater in the historical `ultimate-commerce` 0.1.x package is a migration bridge only and is never part of the canonical Free package.

## Blocking package checks

Every canonical Free release candidate must be built through `scripts/build-wordpress-org-package.py` and pass:

1. deterministic ZIP comparison;
2. archive integrity and single-root validation;
3. canonical plugin identity/text-domain/version/readme alignment;
4. GPL metadata validation;
5. private-update boundary scan;
6. store-specific reference scan;
7. embedded-secret signature scan;
8. forbidden development/artifact file scan;
9. official WordPress Plugin Check against the built package.

The package audit runs against the ZIP that would be submitted, not only against the source tree.

## Official Plugin Check

CI uses the official `wordpress/plugin-check-action@v1` action and points it at the unpacked deterministic package. Experimental checks are disabled for the blocking baseline until deliberately adopted; normal Plugin Check errors/warnings remain visible rather than being globally suppressed.

Plugin Check includes repository/review-oriented checks such as plugin headers/readme, update behaviour, file types, i18n, late escaping and its plugin-review PHPCS checks.

A passing Plugin Check job is necessary but not sufficient for release. Manual repository/security review still applies.

## Secret and private-distribution boundary

`scripts/check-wordpress-org-package.py` rejects release-package text containing known private distribution markers, store-specific FishingClothing references and common embedded credential signatures.

The scanner is a targeted release safeguard, not a replacement for organisation-level secret scanning. Before public launch, GitHub/organisation secret-scanning controls should also be enabled where available.

## WPCS / static analysis

Plugin Check's plugin-review PHPCS checks provide an immediate WordPress review baseline. A dedicated repository-level WPCS/PHPCompatibility/static-analysis toolchain remains a Phase 1 gate to add and pin before WordPress.org submission rather than allowing dependency versions to float silently in CI.

Until that toolchain is pinned, this document must not be interpreted as saying the complete Phase 1 WordPress.org engineering gate is finished.

## Public-repository runner transition

Current validation runs on the private Bad Otter self-hosted runner because GitHub-hosted jobs were not provisioned for this private repository.

**Before repository visibility changes to public, all workflows triggered by untrusted pull requests must move to GitHub-hosted or isolated ephemeral runners.**

Do not make the repository public while a persistent privileged self-hosted runner accepts arbitrary `pull_request` code.

## Submission checklist

Before the first WordPress.org submission/release:

- planned slug is confirmed/accepted rather than merely assumed;
- `Contributors` contains valid WordPress.org usernames;
- plugin/readme/version/stable-tag metadata is aligned;
- Plugin Check is green on the exact release package;
- pinned WPCS/PHPCompatibility/static analysis is green;
- dependency/secret scans are green;
- HPOS and claimed Cart/Checkout Blocks compatibility is tested;
- realistic upgrade from the 0.1.x migration fixture is tested;
- no duplicate active plugin copy is created during identity migration;
- WordPress.org owns Free update delivery after migration;
- source/build instructions and human-readable source are present;
- no private updater, Pro implementation, credentials, test fixtures or nested release archives ship.

A green CI build is evidence for release readiness; it is not itself a release or submission.
