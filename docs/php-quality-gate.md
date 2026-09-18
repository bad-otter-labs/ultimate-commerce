# PHP quality and dependency gate

Ultimate Commerce Free uses a pinned repository-level PHP analysis toolchain in addition to the official WordPress Plugin Check release-package gate.

## Locked toolchain

The root `composer.json` and `composer.lock` are development/review tooling only. They are not copied into the canonical plugin as an installed vendor tree.

The pinned direct tools are:

- WPCS 3.4.1
- PHPCS 3.13.6
- PHPCompatibilityWP 2.1.8
- PHPStan 2.2.14
- phpstan-wordpress 2.0.4
- WordPress stubs 7.1.0
- WooCommerce stubs 11.1.0
- PHPCS Composer installer 1.2.1

CI installs exactly the lockfile and runs `composer audit --locked`; dependency versions must not float during a release review.

## WPCS boundary

`phpcs.xml.dist` uses `WordPress-Extra` but deliberately excludes established mechanical style differences such as tabs, camelCase local variables, brace layout, array formatting and Yoda conditions.

Those exclusions avoid a high-risk repository-wide formatting rewrite while keeping review-oriented WordPress checks active, including security, escaping, database/API usage and i18n.

Two admin request handlers are excluded from the generic nonce-detection sniff because they route verification through the shared `Csrf::require()` security contract. Their capability/CSRF behaviour remains covered by dedicated regression tests.

The discouraged-function base64 sniff is excluded only for the signed guest-token and encrypted-secret implementations. In those locations base64 is transport encoding around authenticated cryptography, not executable-code obfuscation.

The settings import path has a narrow inline exception for reading an already validated local uploaded file.

## PHP compatibility and static analysis

`phpcompatibility.xml.dist` tests the plugin against the declared `8.1-` PHP support range.

`phpstan.neon` runs PHPStan at level 2 against the canonical Free PHP source using WordPress and WooCommerce stubs. `phpstan-bootstrap.php` declares only the plugin constants normally created by the runtime entry file.

## Dependency licences and runtime boundary

`scripts/check-quality-dependency-licenses.py` verifies every locked development package declares an approved open-source licence. The current allowlist is intentionally limited to licences actually present in the pinned graph: MIT, BSD-3-Clause and LGPL-3.0-or-later.

The same check requires the canonical Free package itself to remain GPL-2.0-or-later and fails if its Composer `require` section gains a third-party runtime package without an explicit architecture/release review.

## CI

`.github/workflows/php-quality.yml` is a blocking pull-request/main workflow using the same trust-aware runner routing as other Free validation. External/fork pull requests cannot fall back to the persistent Bad Otter runner.

A green quality job means:

1. the root manifest and lockfile are aligned;
2. the exact locked dependency graph installs;
3. Composer reports no locked dependency advisory failure;
4. dependency licences/runtime dependency boundary pass;
5. review-focused WPCS passes;
6. PHP 8.1+ compatibility passes;
7. PHPStan passes.

This gate complements, rather than replaces, exact ZIP package inspection and official Plugin Check.
