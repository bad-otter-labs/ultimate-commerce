# ADR 0001: Free/Pro product split and WordPress.org distribution

Status: **Accepted**

Date: 2026-09-15

## Context

Ultimate Commerce began as a single privately distributed WooCommerce enhancement plugin using the Bad Otter managed updater. The product is now intended to become a commercial platform with a meaningful Free product, a paid Pro companion and potential future hosted services.

The Free product is also intended for WordPress.org distribution. That creates different packaging/update/compliance requirements from a privately distributed paid plugin.

## Decision

1. The public Free product is **Ultimate Commerce for WooCommerce**.
2. The planned WordPress.org slug is `ultimate-commerce-for-woocommerce`, subject to WordPress.org approval.
3. Free is a meaningful standalone plugin and is distributed/updated through WordPress.org once public.
4. The WordPress.org Free package contains no Bad Otter custom updater and no Pro implementation hidden behind licence checks.
5. The paid companion is **Ultimate Commerce Pro**, planned slug `ultimate-commerce-pro`.
6. Pro is a separate installable plugin and should live in a separate private repository.
7. Pro requires Free and registers modules through documented public Free contracts.
8. Free never depends on Pro.
9. Pro uses the Bad Otter managed release/update pipeline.
10. Hosted services with material ongoing infrastructure cost are commercially distinct from ordinary local Pro modules.
11. Merchant/customer data is not held hostage to licence status.

## Consequences

- the current private pre-release Free package identity/update mechanism must be migrated before public WordPress.org installs exist
- release CI must diverge: WordPress.org compliance gates for Free, Bad Otter managed-release verification for Pro
- public contracts in Free become especially important because Pro and third-party extensions consume them
- the Free repository is expected to become public before WordPress.org submission
- Pro implementation cannot be committed into the public Free package and unlocked remotely
- existing documentation/workflows that assumed every UC plugin updates through Bad Otter must be reconciled

## Related documents

- `BLUEPRINT.md`
- `FOUNDATION.md`
- `ROADMAP.md`
- `BUILD-WORKFLOW.md`
- `AGENTS.md`
