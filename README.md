# Ultimate Commerce

Reusable WooCommerce enhancement platform for building high-quality ecommerce experiences on top of WooCommerce without replacing WooCommerce as the commerce system of record.

## Source of truth

This repository is the sole source of truth for reusable commerce-wide behaviour owned by Ultimate Commerce.

Read `FOUNDATION.md` before designing or implementing features. It is the founding product and engineering constitution for this repository.

Read `BUILD-WORKFLOW.md` before starting implementation work.

## Repository boundary

This repository owns reusable capabilities such as catalogue enhancements, variation UX, cart, checkout enhancements, customer accounts, wishlists, returns, exchanges, tracking, merchandising, promotions, search contracts, stock alerts, recommendations, analytics events and integration adapters.

It must not contain store-specific branding, domain names, theme styling, product taxonomies, supplier-specific business rules, delivery thresholds, return-policy values, marketing content or FishingClothing-specific behaviour.

Store implementations consume Ultimate Commerce through its public PHP contracts, WordPress/WooCommerce hooks, Store API extensions and documented configuration surface.

## Core rule

> WooCommerce owns commerce truth. Ultimate Commerce enhances commerce. Stores configure Ultimate Commerce; they do not fork it.
