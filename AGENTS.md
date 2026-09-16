# Agent Instructions - Ultimate Commerce

Before writing code or documentation in this repository, read:

1. `FOUNDATION.md`
2. `BLUEPRINT.md`
3. `ROADMAP.md`
4. `BUILD-WORKFLOW.md`
5. relevant ADRs under `docs/adr/`

`FOUNDATION.md` owns reusable commerce architecture and WooCommerce/data boundaries. `BLUEPRINT.md` is the master product blueprint: product family, Free/Pro packaging, Hub, shared engines, WordPress.org distribution, security/commercial rules and long-term product scope. If a change creates an apparent conflict, stop and reconcile the source-of-truth documents deliberately.

## Hard repository boundary

This repository is the source of truth for **reusable commerce-wide WooCommerce enhancements in Ultimate Commerce Free** and the public contracts Pro/third parties consume.

Do not add:

- FishingClothing-specific code, copy, brand names or domain logic
- store-specific colours/theme styling
- one merchant's delivery/returns values
- one merchant's supplier mapping
- credentials/secrets
- client-name conditionals
- paid Pro implementation code intended to be licence-gated inside Free

If a requirement is store-specific, it belongs in that store's repository. If it is a paid reusable module, it belongs in the Pro product/repository once that repository exists.

## Product classification rule

Before implementation, classify work as one of:

1. **Free** — meaningful reusable baseline WooCommerce enhancement suitable for the WordPress.org product.
2. **Pro** — advanced conversion, merchandising, customer lifecycle, operations, intelligence or automation capability with substantial merchant value.
3. **Hosted service** — capability with genuine ongoing Bad Otter infrastructure cost such as hosted search, managed communications or future ML services.
4. **Store-specific** — merchant branding, presentation, business-specific policy/configuration or one-off integration.

Ultimate Commerce Hub is part of **Pro**, not a separate purchase/product at launch.

## Architecture rules

- WooCommerce remains authoritative for products, variations, sellable stock, carts, orders, totals, tax, payments and refunds.
- Use public WooCommerce/WordPress APIs and CRUD objects.
- Order code must be HPOS-safe.
- Use Store API extension mechanisms when enriching supported shopper resources.
- UC-owned workflows/data may use namespaced UC APIs/storage.
- External services belong behind provider contracts/adapters.
- Long-running/retryable work belongs in scheduled/background jobs.
- Modules must not create circular dependencies.
- Disabled modules should not load unrelated frontend assets/work.
- Never copy authoritative Woo data into UC merely for convenience.
- Free must never depend on Pro.
- Pro must consume documented public Free contracts rather than secret privileged internals.
- Hub must use UC/Woo service contracts rather than direct database shortcuts.

## Shared-engine rule

Before adding feature-specific storage/logic, check whether the concept belongs in an existing shared platform engine from `BLUEPRINT.md`.

Key shared primitives include:

- Product Relationship Graph
- Availability Service
- Campaign Engine
- Rules & Automation Engine
- Inventory Movement Ledger
- Location Model
- Stored Value Ledger
- Approval Framework
- Alert/Notification Engine
- Audit Trail
- Provider Registry
- Secret Store
- Background Job/idempotency conventions
- Product Template Schema
- Event/Analytics vocabulary

Do not create separate relationship, campaign, balance, inventory-history or approval mechanisms inside individual modules when the shared engine fits.

## Inventory and Hub rules

- Hub is a purpose-built staff application included in Pro; it is not wp-admin with different styling.
- Hub reuses WordPress/Woo identity rather than inventing a second password database.
- Staff can be permitted Hub access while denied everyday wp-admin access.
- Product creation/editing in Hub must use WooCommerce CRUD/public APIs.
- WooCommerce remains authoritative for sellable stock.
- UC may own location allocations, movements, stocktakes, transfers, receiving and purchasing workflow, but these must reconcile deliberately to Woo stock.
- A Hub stock mutation should create an auditable inventory movement with actor/reason/context.
- Product create, product publish, price edit, stock adjust, cost view and stored-value issue permissions are separable.
- Ordinary operational workflows should archive rather than destructively erase historical products/records.

## Stored-value rule

Gift cards, store credit, goodwill credit and future promotional/referral credit share the Stored Value Ledger.

- balances are transaction-ledger based
- redemption/issue must be idempotent and concurrency-safe
- public codes are high-entropy/non-sequential
- full codes/secrets do not enter logs
- monetary order totals/refunds remain owned/executed through Woo/payment APIs

## Security rules

Treat the product as software that may run on large, high-value stores.

- Every request is untrusted.
- Every sensitive object identifier requires capability/ownership authorisation.
- Nonces are CSRF protection, not authorisation.
- REST routes require strict schemas and explicit permission callbacks.
- Customer resources require object-level ownership checks.
- Operational resources may also require location scope.
- Administrative actions use least-privilege UC capabilities rather than blanket `manage_options` where practical.
- Use prepared/database APIs and escape output for its rendering context.
- Provider webhooks require signature/replay/idempotency protection.
- User-configurable outbound URLs require SSRF-safe handling.
- Secrets must never enter logs, diagnostics exports, frontend code or configuration exports.
- Public/guest endpoints require bounded inputs, pagination/rate controls where appropriate and abuse-resistant tokens.
- Background jobs should store identifiers rather than unnecessary PII/credentials and must use bounded retries.
- High-risk workflows such as stock receipt and stored-value redemption must be duplicate-safe.
- Ultimate Commerce must never handle raw card numbers/CVV.

## Free plugin / WordPress.org rule

The public Free plugin is **Ultimate Commerce for WooCommerce**.

Planned directory slug: `ultimate-commerce-for-woocommerce` (subject to WordPress.org acceptance).

The WordPress.org Free package must:

- be independently useful without Pro
- use WordPress.org for public Free updates
- contain no Bad Otter custom updater/update override
- contain no premium implementation hidden behind a licence/payment flag
- contain no unsolicited telemetry/activation ping
- contain no remote executable code or unnecessary remotely hosted assets
- expose human-readable source/build instructions for compiled assets
- pass the WordPress.org-focused CI/release gate defined in `BLUEPRINT.md` and `BUILD-WORKFLOW.md`

Do not reintroduce the old rule that every plugin from this repository must update through Bad Otter. That applies to Pro/store-specific products, not WordPress.org Free.

## Pro release and update rule

Ultimate Commerce Pro is a separate paid companion and should ultimately live in its own private repository.

Pro releases use the Bad Otter managed release/update pipeline:

- GitHub source and automated release artefacts are authoritative.
- Publication uses short-lived GitHub OIDC; no long-lived publisher credentials.
- Production updates use WordPress's native Plugins update flow through the Bad Otter updater.
- Upgrades/migrations must be existing-data safe.
- A Pro release is incomplete until the managed-update path and exact package integrity are verified.

## Cross-repository work

When a store asks for a generic commerce capability:

1. classify it as Free / Pro / hosted / store-specific
2. identify the owning module and any shared engine it requires
3. implement it in the owning UC product
4. expose it through documented public contracts/configuration
5. release the owning UC product
6. let the store consume/configure the capability

Never duplicate UC implementation code into a store repository.

## Architectural changes

Create/revise an ADR when a proposed change materially affects:

- repository/product boundary
- Free/Pro/Hub commercial packaging
- Woo vs UC data ownership
- inventory or stored-value ownership/reconciliation
- database/storage architecture
- public APIs/contracts
- authentication/authorisation model
- module/shared-engine boundaries
- update/distribution strategy
- backwards compatibility policy

Accepted decisions must be reflected back into the blueprint/foundation/workflow where relevant.
