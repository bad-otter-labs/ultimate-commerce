# Ultimate Commerce Build Workflow

This workflow is subordinate to `FOUNDATION.md` and `BLUEPRINT.md`. If a task conflicts with either source of truth, stop and make an explicit architecture decision rather than silently coding around it.

## 1. Before starting any feature

Read:

1. `FOUNDATION.md`
2. `BLUEPRINT.md`
3. `ROADMAP.md`
4. the relevant module code/docs once implementation exists
5. existing public contracts/hooks for the affected area

Then classify the request:

- **Reusable Free commerce capability** -> belongs in Ultimate Commerce Free.
- **Reusable paid capability with clear merchant ROI/operational value** -> belongs in Ultimate Commerce Pro.
- **One merchant's brand, content, provider mapping or business-specific rule** -> belongs in that merchant's repository.
- **WooCommerce already owns the capability** -> integrate/extend Woo rather than replacing it.
- **Infrastructure-heavy hosted capability** -> requires a separate hosted-service decision rather than being hidden inside Pro.

## 2. Design gate

Before implementation, answer:

- What is the owning product: Free, Pro, hosted service or store implementation?
- What is the owning module?
- What data does WooCommerce already own?
- What new data, if any, does UC own?
- Is a new custom table actually required?
- Is there a supported Woo Store API/public PHP API for this?
- What is the public contract for consuming stores and Pro/third parties?
- Can the feature be disabled cleanly?
- What happens when an external provider is down?
- What does this do under HPOS?
- What needs async processing?
- What permissions/ownership checks are required?
- What are the abuse/rate/size bounds?
- What personal/secret data is involved?
- What must be tested?
- If Free, would the implementation remain WordPress.org compliant?

If those answers are unclear, design the boundary before writing the implementation.

## 3. Branch and commit discipline

Use focused feature/fix branches from `main`.

Prefer conventional commit-style messages, for example:

```text
feat(returns): add eligibility policy contract
fix(cart): preserve variation after quantity update
docs: clarify tracking provider boundary
refactor(account): extract order timeline view model
test(checkout): cover guest purchase flow
security(api): enforce return ownership
```

Do not mix unrelated module changes into one commit merely because they are part of the same store project.

## 4. Cross-repository rule

When a store needs generic functionality:

1. classify it as Free or Pro
2. design and implement the reusable capability in the owning UC product
3. expose it through a documented public contract/configuration
4. test it independently of the requesting store where practical
5. release/tag or otherwise establish a consumable UC revision
6. update the store repository to consume/configure that capability

Never solve cross-repository work by copying UC source into a store repository.

Store-specific configuration must not be committed here as a temporary shortcut.

## 5. Product dependency rule

The required direction is:

```text
WooCommerce -> Ultimate Commerce Free -> Ultimate Commerce Pro -> store implementation
```

Rules:

- Free must not reference Pro classes/packages at runtime.
- Pro may require Free and register modules through documented Free extension points.
- Pro must not depend on unpublished secret hooks that prevent legitimate third-party extension.
- A Free feature must remain functional when Pro is absent or deactivated.
- A store may conditionally present Pro-powered UX, but business logic remains in Pro.

## 6. First implementation order

Do not fan out into every planned module immediately.

Follow `ROADMAP.md`. The near-term Free vertical slice is:

1. plugin bootstrap/dependency guard
2. module registry/public extension contract
3. configuration and diagnostics foundations
4. security/capability/request-schema foundations
5. product view model + product-card contract
6. variation/swatch state
7. Store API enrichment needed by the slice
8. quick add
9. cart drawer primitives
10. checkout handoff to WooCommerce
11. account/order view-model hooks

Then prove:

```text
Woo product -> UC product contract -> variant/quick add -> cart -> checkout -> Woo order
```

Only after this boundary works under realistic load should broader modules accelerate.

## 7. Definition of done for a feature

A feature is not done because it works in one browser on one store.

Relevant completion criteria include:

- owning product/module is correct
- no store-specific logic was introduced
- public contract documented where applicable
- permissions and object-ownership checks implemented
- nonce/CSRF handling is paired with authorisation rather than replacing it
- strict request schema/validation exists
- output escaping is correct for context
- query/request sizes are bounded
- HPOS-safe order access
- async work queued where appropriate
- retries are bounded/idempotent where appropriate
- module disable path works
- assets only load where needed
- automated tests cover business rules and security boundary
- customer-facing interaction is keyboard accessible
- failure states are handled
- logs do not leak credentials/personal data
- migrations are versioned if storage changed
- upgrade path considered
- Free feature passes WordPress.org-specific rules
- Pro feature degrades safely if entitlement/hosted dependencies are unavailable

## 8. Security gate

Security review is mandatory for every state-changing/admin/customer-data feature.

Review:

- authentication requirement
- least-privilege capability
- object-level ownership
- CSRF protection
- REST/AJAX schema
- validation/sanitisation
- output escaping
- SQL/data access
- guest-token entropy/expiry where applicable
- rate/size/pagination limits
- SSRF/outbound HTTP behaviour
- webhook signature/replay/idempotency behaviour
- secrets/PII logging
- audit-event requirements
- privacy export/erase/retention implications

High-risk changes should add regression tests for the exact trust boundary they introduce.

## 9. Compatibility gate

Before each release, test against the supported matrix recorded in plugin metadata/CI, including:

- minimum supported PHP/WordPress/WooCommerce
- current stable WordPress/WooCommerce
- HPOS enabled
- Cart/Checkout Blocks for affected features

Only declare compatibility after testing it.

Additional compatibility claims for caches, multilingual systems, multi-currency, themes or page builders require evidence and should be tracked explicitly.

## 10. Database changes

Database changes require:

1. schema proposal
2. data classification (operational/personal/secret)
3. access-pattern/index review
4. migration version
5. idempotent/resumable migration implementation where practical
6. upgrade test against populated fixtures
7. rollback/recovery thinking
8. export/erase/retention consideration where personal data is involved

Never make production operation depend on a developer manually running undocumented SQL.

## 11. Integration changes

New external providers should implement an existing provider-neutral contract where possible.

If no contract fits, define provider-neutral behaviour before provider-specific code.

Provider adapters must translate:

- authentication/configuration
- request/response vocabulary
- errors/timeouts
- retries
- provider IDs/statuses
- webhook events/signatures

into UC concepts.

Provider code must use WordPress HTTP APIs, maintain TLS verification and follow SSRF-safe endpoint rules. Secrets never enter normal logs or diagnostics exports.

## 12. WordPress.org Free release discipline

Ultimate Commerce Free is the public **Ultimate Commerce for WooCommerce** plugin.

Planned directory slug: `ultimate-commerce-for-woocommerce`, subject to WordPress.org acceptance.

Public Free releases are distributed through WordPress.org. The public Free package must not use the Bad Otter plugin updater.

### Required Free lifecycle

```text
implemented
 -> committed
 -> PR
 -> generic CI passed
 -> WordPress.org compliance/security CI passed
 -> merged
 -> reproducible package built
 -> package inspected
 -> release candidate tested on representative WooCommerce site
 -> WordPress.org SVN/submission/release process
 -> directory/update availability verified
```

### Required WordPress.org package properties

- meaningful standalone functionality
- no Pro implementation hidden behind licence checks
- no third-party custom updater/update override
- no unsolicited tracking/activation ping
- no remote executable code
- normal local UI assets included in package
- WordPress/WooCommerce public APIs used
- human-readable source/build instructions for compiled assets
- translation-ready metadata/strings
- no credentials, environment files, dev junk or nested release ZIPs

### Required Free CI gate

Before a WordPress.org-targeted release:

- official Plugin Check has no blocking errors
- PHPCS/WPCS passes
- PHP compatibility/static analysis passes
- dependency/security audits pass
- dependency licence checks pass
- secret scan passes
- package inspection passes
- plugin header/readme/stable tag/version alignment passes
- supported WordPress/WooCommerce matrix passes
- HPOS passes
- Blocks tests pass where relevant
- critical E2E flow passes
- accessibility baseline passes for affected UI
- permission/object-ownership/CSRF/schema regression tests pass

Passing CI does not guarantee WordPress.org acceptance; reviewer feedback must be treated as a release blocker and resolved cleanly.

## 13. Pro release discipline

Ultimate Commerce Pro is a separate paid companion and should live in its own private repository.

Pro follows semantic versioning and uses the canonical Bad Otter managed release/update workflow.

Required lifecycle:

```text
implemented
 -> committed
 -> PR
 -> CI passed
 -> merged
 -> canonical package built
 -> Bad Otter draft candidate
 -> exact package promoted to Stable
 -> live WordPress managed-update lookup verified
 -> downloaded package checksum/byte identity verified
 -> immutable Git tag/GitHub Release
```

Rules:

1. GitHub source and automated release artefacts are authoritative.
2. Hand-built production ZIPs are not authoritative.
3. Publication uses short-lived GitHub OIDC with the Bad Otter release-publisher audience.
4. Long-lived Bad Otter publisher credentials must not be stored in source/repository secrets as a normal publication mechanism.
5. Bad Otter handles package inspection, release metadata, entitlement-aware delivery and managed WordPress updates.
6. Existing-data migrations must be safe for in-place upgrades.
7. A Pro release is incomplete until the WordPress managed-update path and exact package integrity are verified.

Canonical Bad Otter publication endpoint currently used by the organisation:

```text
POST https://api.badotter.io/v1/release-publisher/ingest
```

OIDC audience:

```text
badotter-release-publisher
```

If the Bad Otter contract changes, update this documentation deliberately rather than creating a parallel updater.

## 14. Pre-WordPress.org development builds

Before Free is accepted into WordPress.org, internal QA may use temporary GitHub/Bad Otter/manual release artifacts.

Those builds are **development distribution only**. They do not change the public product rule that Free production updates come from WordPress.org once launched.

Before public installs exist, migrate the Free package identity cleanly to the approved WordPress.org slug/main plugin file so users are not forced through a disruptive identity change later.

## 15. Release metadata and version discipline

Every released plugin should have:

- one unambiguous semantic version across required version sources
- changelog entry
- passing automated checks
- migration check
- compatibility check
- reproducible distributable build

A version number existing in source code does not mean that version has been released.

## 16. Architecture decisions

Create an ADR when a change affects any of the following:

- repository boundary
- Free/Pro/hosted product ownership
- dependency direction
- Woo vs UC data ownership
- database/storage architecture
- public API/contract strategy
- authentication/authorisation model
- module boundaries
- WordPress.org distribution model
- replacement of a core provider abstraction
- backwards compatibility policy

Once accepted, reconcile the relevant rule back into `FOUNDATION.md` and/or `BLUEPRINT.md` so the source-of-truth documents stay authoritative.

## 17. Roadmap gate

Major feature work should map to an active phase in `ROADMAP.md`.

Do not build later-phase complexity when an earlier dependency is not stable unless there is a documented reason.

## 18. North-star check

Before merging, ask:

> Could this code be installed on a completely different WooCommerce store without knowing who requested the feature, and is it in the correct Free/Pro/store layer?

If the answer is no, either make it reusable/configurable, move it to the correct paid product, or move it to the store repository.
