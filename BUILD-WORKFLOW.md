# Ultimate Commerce Build Workflow

This workflow is subordinate to `FOUNDATION.md`. If a task conflicts with the foundation, stop and make an explicit architecture decision rather than silently coding around it.

## 1. Before starting any feature

Read:

1. `FOUNDATION.md`
2. the relevant module code/docs once implementation exists
3. existing public contracts/hooks for the affected area

Then classify the request:

- **Reusable commerce capability** -> belongs in Ultimate Commerce.
- **One merchant's brand, content, provider mapping or business-specific rule** -> belongs in that merchant's repository.
- **WooCommerce already owns the capability** -> integrate/extend Woo rather than replacing it.

## 2. Design gate

Before implementation, answer:

- What is the owning module?
- What data does WooCommerce already own?
- What new data, if any, does UC own?
- Is a new custom table actually required?
- Is there a supported Woo Store API/public PHP API for this?
- What is the public contract for consuming stores?
- Can the feature be disabled cleanly?
- What happens when an external provider is down?
- What does this do under HPOS?
- What needs async processing?
- What permissions/ownership checks are required?
- What must be tested?

If those answers are unclear, design the boundary before writing the implementation.

## 3. Branch and commit discipline

Use focused feature/fix branches from `main` once active development begins.

Prefer conventional commit-style messages, for example:

```text
feat(returns): add eligibility policy contract
fix(cart): preserve variation after quantity update
docs: clarify tracking provider boundary
refactor(account): extract order timeline view model
test(checkout): cover guest purchase flow
```

Do not mix unrelated module changes into one commit merely because they are part of the same store project.

## 4. Cross-repository rule

When a store needs new generic functionality:

1. design and implement the reusable capability here
2. expose it through a documented public contract/configuration
3. test it independently of the requesting store where practical
4. release/tag or otherwise establish a consumable UC revision
5. update the store repository to consume/configure that capability

Never solve cross-repository work by copying UC source into a store repository.

Store-specific configuration must not be committed here as a temporary shortcut.

## 5. First implementation order

Do not fan out into every planned module immediately.

Build the first vertical slice in this order:

1. plugin bootstrap/dependency guard
2. module registry
3. configuration and diagnostics foundations
4. product view model + product-card contract
5. variation state
6. Store API enrichment needed by the slice
7. cart enhancement primitives
8. checkout extension point
9. account/order view model
10. return eligibility/request shell

Then prove:

```text
Woo product -> UC product contract -> cart -> checkout -> Woo order -> account -> return entry
```

Only after this boundary works should broader modules accelerate.

## 6. Definition of done for a feature

A feature is not done because it works in one browser on one store.

Relevant completion criteria include:

- ownership boundary is correct
- no store-specific logic was introduced
- public contract documented where applicable
- permissions/ownership checks implemented
- validation/sanitisation and output escaping handled
- HPOS-safe order access
- async work queued where appropriate
- module disable path works
- assets only load where needed
- automated tests cover business rules
- customer-facing interaction is keyboard accessible
- failure states are handled
- logs do not leak credentials/personal data
- migrations are versioned if storage changed
- upgrade path considered

## 7. Compatibility gate

Before each release, test against the supported matrix recorded in plugin metadata/CI, including:

- minimum supported PHP/WordPress/WooCommerce
- current stable WordPress/WooCommerce
- HPOS enabled
- Cart/Checkout Blocks for affected features

Only declare compatibility after testing it.

## 8. Database changes

Database changes require:

1. schema proposal
2. access-pattern/index review
3. migration version
4. idempotent/resumable migration implementation where practical
5. upgrade test against populated fixtures
6. rollback/recovery thinking

Never make production operation depend on a developer manually running undocumented SQL.

## 9. Integration changes

New external providers should implement an existing contract where possible.

If no contract fits, define the provider-neutral behaviour before writing provider-specific code.

Provider adapters must translate:

- authentication/configuration
- request/response vocabulary
- errors/timeouts
- retries
- provider IDs/statuses

into UC concepts.

## 10. Release discipline

Ultimate Commerce follows semantic versioning.

Every release should have:

- version bump
- changelog entry
- passing automated checks
- migration check
- compatibility check
- reproducible distributable build

A release must not contain merchant credentials, environment files or store-specific configuration.

### Bad Otter plugin distribution and managed updates

Every WordPress plugin produced from this repository must use the standard Bad Otter build, publication and managed-update workflow.

The required lifecycle is:

1. GitHub `main` is the canonical source.
2. Development happens on a focused branch and is merged only after validation passes.
3. A releasable plugin has one unambiguous semantic version across all required version sources, including the WordPress plugin header and any plugin version constant/manifest used by the package.
4. The GitHub release workflow builds the canonical installable ZIP from repository source. Hand-built production ZIPs are not authoritative.
5. The workflow validates the package, generates release metadata/checksums and publishes the release through the Bad Otter Release Publisher.
6. Publication uses short-lived GitHub OIDC identity with the Bad Otter release-publisher audience; long-lived Bad Otter publisher credentials must not be stored in the repository.
7. Bad Otter is responsible for package inspection, release metadata, entitlement-aware delivery where applicable, and managed WordPress updates.
8. A corresponding immutable Git tag/GitHub Release should identify the source revision for the published artefact.
9. The first installation of a plugin may be performed manually from the canonical release ZIP.
10. After that first install, normal upgrades must be delivered through WordPress's native Plugins update experience via the Bad Otter managed updater. Repeated manual ZIP replacement is a recovery/development procedure, not the production update model.
11. The plugin must retain the stable plugin slug/package identity required for WordPress to recognise later versions as updates to the installed plugin.
12. Database migrations and upgrade routines must run safely when WordPress updates the plugin in place; an update must never assume a clean install.
13. A release is not considered complete until publication has succeeded and the new version has been verified as discoverable/installable through the WordPress update path on a representative installation.

The canonical Bad Otter publication endpoint currently used by the organisation's plugin workflow is:

```text
POST https://api.badotter.io/v1/release-publisher/ingest
```

with GitHub OIDC audience:

```text
badotter-release-publisher
```

When implementing this repository's release workflow, use the current canonical Bad Otter plugin workflow as the reference rather than inventing a parallel updater/release system. If the Bad Otter release contract changes, update this workflow documentation deliberately.

Track release state explicitly:

```text
implemented -> committed -> PR -> CI passed -> merged -> built -> published -> WordPress update verified
```

A version number existing in source code does not mean that version has been released.

## 11. Architecture decisions

Create an ADR when a change affects any of the following:

- repository boundary
- dependency direction
- Woo vs UC data ownership
- database/storage architecture
- public API/contract strategy
- authentication/authorisation model
- module boundaries
- replacement of a core provider abstraction
- backwards compatibility policy

Once accepted, reconcile the relevant rule back into `FOUNDATION.md` so the foundation stays authoritative.

## 12. North-star check

Before merging, ask:

> Could this code be installed on a completely different WooCommerce store without knowing who requested the feature?

If the answer is no, either make it configurable/generic or move it to the store repository.
