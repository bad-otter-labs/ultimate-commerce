# Ultimate Commerce

**Ultimate Commerce** is the commerce experience layer for WooCommerce: a reusable platform for building stronger product discovery, conversion, merchandising, customer and aftercare experiences without replacing WooCommerce as the commerce system of record.

The public Free product is planned as **Ultimate Commerce for WooCommerce**. The paid companion is **Ultimate Commerce Pro**.

## Source of truth

Read these before designing or implementing features:

1. `FOUNDATION.md` — founding technical architecture, WooCommerce ownership and reusable domain boundaries.
2. `BLUEPRINT.md` — product family, Free/Pro split, WordPress.org distribution, security, commercial model and scale requirements.
3. `ROADMAP.md` — sequenced product and release roadmap.
4. `BUILD-WORKFLOW.md` — implementation, security and release gates.

If older documentation conflicts with `BLUEPRINT.md` on Free/Pro packaging, WordPress.org distribution, managed updates or product naming, the blueprint wins and the older document must be reconciled deliberately.

## Product family

- **Ultimate Commerce for WooCommerce (Free)** — meaningful standalone WooCommerce enhancements, intended for WordPress.org distribution and WordPress.org updates.
- **Ultimate Commerce Pro** — separate paid companion plugin, distributed through Bad Otter managed updates.
- **Hosted services (future)** — optional infrastructure-backed capabilities such as hosted search or AI recommendations, commercially separate from ordinary local Pro modules where ongoing infrastructure cost exists.

## Repository boundary

This repository owns reusable Free commerce capabilities and the public contracts used by stores, Pro and third-party extensions.

It must not contain store-specific branding, domain names, theme styling, product taxonomies, supplier-specific business rules, delivery thresholds, return-policy values, marketing content or FishingClothing-specific behaviour.

Paid Pro implementation code must not be hidden inside the Free package behind a licence flag.

Store implementations consume Ultimate Commerce through public PHP contracts, WordPress/WooCommerce hooks, Store API extensions and documented configuration surfaces.

## Core rule

> WooCommerce owns commerce truth. Ultimate Commerce enhances commerce. Stores configure Ultimate Commerce; they do not fork it.

## Product principle

> Ultimate Commerce modules should become more valuable when used together while remaining independently useful.
