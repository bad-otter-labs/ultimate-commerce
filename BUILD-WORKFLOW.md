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
