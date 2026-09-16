# Ultimate Commerce

**Ultimate Commerce** is the commerce experience and retail operations layer for WooCommerce: a reusable platform for stronger product discovery, conversion, merchandising, customer lifecycle and staff operations without replacing WooCommerce as the commerce system of record.

The public Free product is planned as **Ultimate Commerce for WooCommerce**. The paid companion is **Ultimate Commerce Pro**. **Ultimate Commerce Hub is included within Pro**, not sold as a separate add-on at launch.

## Source of truth

Read these before designing or implementing features:

1. `FOUNDATION.md` — founding technical architecture, WooCommerce ownership and reusable domain boundaries.
2. `BLUEPRINT.md` — **master product blueprint** covering Free/Pro, Hub, shared engines, WordPress.org distribution, security, commercial model, scale and long-term product scope.
3. `ROADMAP.md` — sequenced outcome-based build and release roadmap.
4. `BUILD-WORKFLOW.md` — implementation, security and release gates.
5. `docs/adr/` — accepted architecture decisions.

If the documents appear to conflict, do not silently choose the convenient interpretation. Reconcile them deliberately through the architecture decision process.

## Product family

- **Ultimate Commerce for WooCommerce (Free)** — meaningful standalone WooCommerce enhancements intended for WordPress.org distribution and WordPress.org updates.
- **Ultimate Commerce Pro** — separate paid companion distributed through Bad Otter managed updates.
- **Ultimate Commerce Hub** — mobile-first staff operations application included in Pro for catalogue, inventory and operational workflows without requiring everyday wp-admin use.
- **Hosted services (future)** — optional infrastructure-backed capabilities such as hosted search, communications or AI/ML services where there is genuine ongoing infrastructure cost.

## Pro pillars

Ultimate Commerce Pro is designed around seven connected areas:

- **Convert** — advanced variants, bundles, upsells, cross-sells, cart intelligence
- **Merchandise** — relationships, campaigns, collections, badges/overlays, notice bars, search merchandising
- **Personalise** — My Size, profiles and recommendations
- **Serve** — customer portal, returns, exchanges, tracking, gift cards and store credit
- **Operate** — Hub, catalogue creation, inventory, locations, stocktake, transfers and purchasing
- **Understand** — stock/return/merchandising analytics and value attribution
- **Automate** — event/rule/action automation connecting the platform

## Repository boundary

This repository owns reusable Free commerce capabilities and the public contracts used by stores, Pro and third-party extensions.

It must not contain store-specific branding, domains, theme styling, product taxonomies, one-merchant supplier rules, delivery thresholds, return-policy values, marketing content or FishingClothing-specific behaviour.

Paid Pro implementation code must not be hidden inside the Free package behind a licence flag.

Store implementations consume Ultimate Commerce through public PHP contracts, WordPress/WooCommerce hooks, Store API extensions and documented configuration surfaces.

## Core rules

> **WooCommerce owns commerce truth. Ultimate Commerce enhances commerce. Stores configure Ultimate Commerce; they do not fork it.**

> **Ultimate Commerce modules should become more valuable when used together while remaining independently useful.**
