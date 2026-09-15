# Agent Instructions - Ultimate Commerce

Before writing code or documentation in this repository, read `FOUNDATION.md` and `BUILD-WORKFLOW.md`.

## Hard repository boundary

This repository is the source of truth for **reusable commerce-wide WooCommerce enhancements**.

Do not add:

- FishingClothing-specific code, copy, brand names or domain logic
- store-specific colours/theme styling
- one merchant's delivery/returns values
- one merchant's supplier mapping
- credentials/secrets
- client-name conditionals

If a requirement is store-specific, it belongs in that store's repository.

## Architecture rules

- WooCommerce remains authoritative for products, variations, stock, carts, orders, totals, tax, payments and refunds.
- Use public WooCommerce/WordPress APIs and CRUD objects.
- Order code must be HPOS-safe.
- Use Store API extension mechanisms when enriching supported shopper resources.
- UC-owned workflows/data may use namespaced UC APIs/storage.
- External services belong behind provider contracts/adapters.
- Long-running/retryable work belongs in scheduled/background jobs.
- Modules must not create circular dependencies.
- Disabled modules should not load unrelated frontend assets/work.
- Never copy authoritative Woo data into UC merely for convenience.

## Cross-repository work

When a store asks for a generic commerce capability, implement and expose it here first. The store should then consume the public capability. Never duplicate UC implementation code into the store repository.

## Architectural changes

If a proposed change conflicts with `FOUNDATION.md`, do not silently proceed. Document the architecture decision and update the foundation deliberately if the new decision is accepted.
