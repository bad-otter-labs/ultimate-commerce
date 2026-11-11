# ADR 0003 — Optional Labs foundation discovery and explicit commerce authorities

**Decision:** Accepted for the read-only compatibility boundary, 11 November 2026. **Implementation status:** Labs v0 discovery adapter and tests proposed in this branch; catalogue, stock, permissions and stored-value network adapters are **not implemented or activated**.

## Why

Ultimate Commerce Free is an independently useful WordPress.org-oriented WooCommerce enhancement. Pro is its optional paid companion, including Ultimate Commerce Hub. Neither should be rewritten as a central Bad Otter business application. The shared Labs Superpowers v0 contract can recognise modules without becoming their lifecycle or data owner. The Platform foundation remains a private adoption candidate, not a mandatory or published service.

## Identity and discovery mapping

| Surface | Existing identity and authority | Labs discovery identity |
| --- | --- | --- |
| Canonical Free | Current WP plugin source `ultimate-commerce-for-woocommerce`; existing text domain and installed WP slug are preserved | `ultimate-commerce-for-woocommerce/core` plus mapped built-in module keys |
| Historical Free upgrade bridge | Existing managed package `ultimate-commerce/core`, product `ultimate-commerce`, and its update path remain unchanged | No implicit migration or identity re-registration |
| Pro | Existing managed package `ultimate-commerce-pro/core`, product `ultimate-commerce-pro`, entitlement `ultimate-commerce-pro-core` | `ultimate-commerce-pro/core` plus mapped Pro module keys |

This is **a separate Labs ID mapping**, not a rename of any plugin directory, installed module key, package identifier, API constant, managed-update identity, licence or entitlement. Underscores in UC local keys become hyphens in Labs identities; collisions cause discovery to fail rather than silently map two modules to one ID. Required dependencies are looked up in the actual UC registry, not inferred from module name. Unmapped extension products are not silently enlisted.

The canonical Free `LabsModuleMap` produces declarations conforming to `labs.superpowers.module.v0`; the static Free core manifest supports offline package inspection. `Plugin::labsModuleManifests()` is a read-only method callers may invoke **after** `ultimate_commerce_modules_registered` / `ultimate_commerce_loaded`. A Pro-side adapter may supply its own trusted product/entitlement mapping. No runtime Superpowers dependency, global hook, network request, new database, cloud sign-in, staff account or entitlement check is introduced. Hosts may explicitly pass declarations to an already installed, verified Labs v0 `ModuleRegistry`; doing so neither boots UC modules nor authenticates an actor.

Discovery manifests deliberately advertise **no staff capabilities or domain authorities**. Those require explicit, versioned provider mapping and trusted actor/business/resource checks. Mere module presence or a valid software licence never grants an operational permission. Existing Free Module API 1.0 and `ultimate_commerce_register_modules` retain sole registration and boot authority.

## Catalogue and product reference boundary

Standalone mode: WooCommerce product/variation CRUD, IDs, commerce prices, Store API queries, catalogue display and publication remain authoritative for the WooCommerce store. The shared Product Master must not create another writable local catalogue or silently overwrite Woo product state.

Future connected mode: a merchant explicitly enrols an installation, business, catalogue scope and selected product-reference adapter. Only a versioned, permission-checked adapter may resolve Labs product IDs to Woo IDs, with durable link provenance and reconciliation. Remote unavailability leaves standalone WooCommerce functioning; no code in this change connects to Independent, a live store or a central catalogue.

## Stock authority is selected, never inferred

1. **Standalone WooCommerce:** WooCommerce alone owns sellable stock, decreases, cancellations, returns and native order lifecycle. Existing Pro availability and recommendations may read it without changing that ownership.
2. **Enrolled central Stock/EPOS:** A separately authorised future integration specifies the business, location, product scope, synchronisation mode and one central writable authority. Before enabling it, order/stock operations must switch to a central-reservation/commit contract and explicitly disable Woo's competing stock decrement for the selected scope. Woo may show a derived inventory view but is not an independent writer.

The selection is per enrolled stock scope, not a global setting silently toggled by the presence of a plugin. A scope must never have two independent decrements. The connected-mode API is **intentionally not guessed or implemented** here. Required future acceptance includes installation/business binding, idempotent order and reservation keys, last-item concurrency, transaction boundaries, expiry/release, cancellation/refund and HPOS event ordering, replay/retry protection, reconciliation, outage fail-closed checkout rules, conflict visibility, rollback and merchant-confirmed cutover. Do not interpret this design as permission to ship a production stock connector.

## Staff, Hub and financial boundaries

- **Standalone Hub:** keep supported local WordPress permissions and the existing Pro lifecycle. No mandatory Independent Staff Area, Labs identity login or second credential store is created. Future shared permission vocabulary is consumed only through a verified policy-provider adapter that validates merchant scope, actor, business and exact resource. Licences and staff grants are orthogonal.
- **Licensing:** the existing badotter.io commercial issuer, managed update channel and Pro entitlement remain the only software rights authority. No new issuer or fabricated entitlement is introduced by module discovery.
- **Gift cards and stored value:** Pro's existing append-only ledger, encryption keys, issue/redemption idempotency, balances, transaction histories, QR compatibility, correction and redemption rights remain authoritative. No double-writable voucher ledger, liability transfer, key rotation or cutover follows from this ADR. Shared vouchers require a separately approved, tested adapter or migration.

## Validation, rollout and rollback

The pure PHP compatibility tests check declared core identity, Free standalone, Pro dependency ownership, existing paid entitlement, collision/missing-dependency failure, no implied staff/data rights and explicit-only foundation registration. Free's hosted validation runs them alongside all existing checks. Pro maintains its own independent manifest/adapter tests and the existing Free/Pro integration contract. A future disposable end-to-end Superpowers consumer test should prove the exact private candidate before deployment.

Rollback is deleting or disabling the optional adapter caller; UC registry, WooCommerce commerce records, plugin updates and customer balances remain unchanged. **No site deployment, migration, publication, central service activation or financial operation is part of this decision.**
