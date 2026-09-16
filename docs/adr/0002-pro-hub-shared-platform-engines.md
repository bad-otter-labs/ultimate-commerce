# ADR 0002 — Ultimate Commerce Pro includes Hub and uses shared platform engines

Status: **Accepted**

Date: 2026-09-16

## Context

The commercial/product design expanded beyond storefront enhancements into connected merchandising, customer lifecycle and retail operations capabilities.

Major planned Pro areas now include:

- bundles, upsells, cross-sells and Frequently Bought Together
- smart out-of-stock alternatives
- campaign-driven badges, overlays and notice bars
- recommendations, My Size and customer portal
- returns, exchanges and tracking
- gift cards, store credit and stored value
- staff-facing catalogue/inventory operations
- multi-location stock, receiving, stocktake, transfers and purchasing
- alerts, automation and analytics

Several of these areas need the same underlying concepts. Building separate mini-engines inside each feature would create inconsistent data, duplicate logic and difficult migrations.

The staff-facing operational interface also raised a commercial packaging question: whether Ultimate Commerce Hub should be a separately purchased product.

## Decision

### Commercial packaging

**Ultimate Commerce Hub is a first-class subsystem of Ultimate Commerce Pro and is included with the Pro purchase.**

At launch, Hub is not sold as a separate add-on and standard self-hosted Hub usage is not priced per staff seat.

A future Business/Enterprise tier may charge for materially different scale, governance, integrations or support, but normal Pro functionality should not be fragmented into many separately purchased micro-addons.

### Hub architecture

Hub is a purpose-built, mobile-first staff application for operational workflows. It should allow appropriately authorised staff to work without entering wp-admin.

Hub reuses WordPress/WooCommerce identity and commerce APIs rather than creating a parallel password database, product catalogue, order store or payment system.

WooCommerce remains authoritative for products, variations and sellable stock used by commerce flows. Ultimate Commerce may own the operational workflow around those objects, including locations, movements, stocktakes, transfers, supplier/purchasing records and approvals, with explicit reconciliation to Woo stock.

### Shared platform engines

The following concepts are shared platform primitives and should be implemented once rather than independently by each feature:

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

Gift cards, return credit and future goodwill/promotional credit share the Stored Value Ledger.

Bundles, Complete the Look, colour siblings, alternatives, accessories and OOS substitution share the Product Relationship Graph.

Notice bars, badges, sale overlays, collections and campaign search boosts share the Campaign Engine.

Stock receiving, adjustment, damage, stocktake and transfers share the Inventory Movement Ledger.

High-risk actions such as large stock changes, product publication, manual stored-value issue, purchase orders and override workflows may share the Approval Framework.

## Consequences

- Pro stays commercially simple while remaining internally modular.
- Hub can become a major reason to buy Pro without creating another licence dependency.
- Cross-module data becomes a defensible product advantage.
- Shared engines require careful schemas, migrations and public contracts before broad feature implementation.
- Operational modules must not create a second unexplained product/order/stock truth alongside WooCommerce.
- Stored value and inventory operations become higher-security domains requiring concurrency, idempotency and audit controls.
- `BLUEPRINT.md` is updated as the detailed product source of truth and `ROADMAP.md` sequences delivery.
