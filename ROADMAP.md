# Ultimate Commerce Master Roadmap

Status: **Sequenced outcome-based roadmap**

This roadmap turns `BLUEPRINT.md` and `FOUNDATION.md` into implementation order. It is intentionally outcome-based rather than date-based. A phase exits only when its product, security, performance and upgrade gates are satisfied.

The roadmap serves four simultaneous goals:

1. build a genuinely useful WordPress.org Free product
2. create a premium Pro product with obvious merchant ROI
3. grow Pro into a coherent commerce + retail-operations platform rather than a bag of unrelated add-ons
4. use FishingClothing.co.uk as the first serious proving ground without creating store-specific code paths

## Roadmap principles

- Free public contracts come first; Pro extends them
- security/WordPress.org compliance is built in from the start
- WooCommerce remains commerce truth
- shared platform concepts are built once as engines/ledgers/contracts
- each phase must be valuable before the whole vision is complete
- merchant ROI and operational workload reduction drive Pro priorities
- no major release is complete without realistic upgrade testing
- phases may move based on product evidence, but architectural boundaries may not be bypassed

---

# Phase 0 — Product split and constitutional reset

**Goal:** align the codebase with the Free/Pro/WordPress.org product model before more feature work compounds old assumptions.

## Deliverables

- public name: **Ultimate Commerce for WooCommerce**
- brand: **Ultimate Commerce**
- planned Free slug `ultimate-commerce-for-woocommerce`
- Pro identity `ultimate-commerce-pro`
- private `bad-otter-labs/ultimate-commerce-pro` repository
- Free has no runtime dependency on Pro
- public module/extension registration contract Pro will consume
- migration plan from current private pre-release Free identity to WordPress.org identity
- Free release path separated from Bad Otter managed Pro release path
- documentation/CI reconciled with the master blueprint
- Hub explicitly defined as a Pro subsystem, not a separate purchase

## Exit gate

- no architecture document contradicts Free/Pro distribution
- Free and Pro package identities are explicit
- Free works independently with Pro absent
- Pro can register modules only through documented Free contracts

## Commercial outcome

The product avoids an expensive identity/distribution migration after users exist.

---

# Phase 1 — Security and WordPress.org engineering baseline

**Goal:** make the development process capable of producing software safe enough for serious merchants and reviewable by WordPress.org.

## Platform/security deliverables

- dedicated UC capability model
- object-ownership authorisation helpers
- strict REST route/schema conventions
- shared CSRF/nonce conventions
- secure guest-token utility
- secret-store abstraction
- SSRF-safe provider HTTP conventions
- signed webhook gateway with replay/idempotency controls
- structured audit-event contract
- privacy/data classification rules
- WordPress personal-data exporter/eraser foundations
- Action Scheduler job conventions
- pagination/query-limit standards
- idempotency/locking conventions for high-risk workflows
- security disclosure process / `SECURITY.md`

## CI / release gate

- Plugin Check
- PHPCS + WordPress Coding Standards
- PHP compatibility/static analysis
- Composer/npm vulnerability scanning
- secret scanning
- release-package inspection
- permission/ownership regression tests
- REST/CSRF/schema tests
- HPOS compatibility tests
- Cart/Checkout Blocks tests where relevant

## Exit gate

Deliberately vulnerable fixtures cannot bypass expected permission, ownership, CSRF or request-schema controls.

---

# Phase 2 — Free storefront value: catalogue, variants and cart

**Goal:** create a Free experience merchants would install even if Pro did not exist.

## Catalogue / Product Cards

- reusable Woo product view model
- product-card contract
- media/secondary-media state
- brand/title/price/rating contracts
- configurable card components
- manual badge support
- no N+1 listing queries

## Variants & Swatches

- text/button selectors
- colour/image swatches
- selected state
- variation imagery
- available/unavailable state
- basic low-stock presentation
- deep-link state where appropriate

## Quick Add

- variation-aware quick add
- accessible dialog/state management
- stock-safe add-to-cart

## Basic Cart Drawer

- Woo Store API/cart truth
- add/remove/update quantity
- accessible focus management
- error/loading states
- extension slots for future Pro conversion modules

## FishingClothing proving slice

```text
PLP product card
 -> variation/size selection
 -> quick add
 -> cart drawer
 -> Woo totals
 -> checkout
 -> real Woo order
```

## Exit gate

- performant on a large variable-product fixture
- accessibility baseline passes
- HPOS/Blocks matrix green
- no store-specific code in UC

---

# Phase 3 — Free retention and merchant usability

**Goal:** make Free complete enough to stand alone rather than feeling like a Pro demo.

## Deliverables

- basic wishlist
- recently viewed
- basic stock presentation
- basic account/order hooks/view models
- settings import/export
- mature diagnostics/system status
- module enable/disable UX
- documentation for theme/store integration
- translation readiness
- uninstall/data-retention controls

## Exit gate

A normal Woo merchant can configure/use Free without Bad Otter account, Pro, custom code or external service.

---

# Phase 4 — WordPress.org public beta and Free 1.0

**Goal:** establish Free as the acquisition channel for the product family.

## Submission preparation

- transition package identity to `ultimate-commerce-for-woocommerce`
- remove Bad Otter updater from public directory package
- public/reviewable source
- human-readable JS/CSS source and build instructions
- WordPress.org readme/screenshots/assets
- privacy/external-service disclosures
- restrained Pro discovery UI
- translation template
- support/documentation links

## Mandatory gate

- Plugin Check has no blocking issues
- WPCS/PHPCS green
- dependency/security scans green
- no unsolicited telemetry
- no remote executable dependency for normal Free operation
- package contains production files only
- supported WordPress/WooCommerce matrix green
- HPOS/Blocks green
- accessibility baseline green
- pre-release data upgrade validated

## Exit gate

WordPress.org submission is accepted or reviewer feedback is resolved without compromising the architecture.

---

# Phase 5 — Pro foundation and first paid conversion release

**Goal:** ship the first paid product with immediate, understandable merchant ROI.

## Pro platform

- Pro dependency guard requiring Free
- Bad Otter entitlement/update client
- staging/development licence treatment
- Pro module registration through Free contracts
- safe licence-expiry behaviour
- Pro diagnostics

## Advanced Variants

- linked/sibling colours
- colour families
- size availability on cards
- preferred size
- `Available in my size` contract
- advanced variation/linked-product strategies

## Advanced Cart & Conversion

- free-delivery progress
- cart recommendations
- contextual cross-sells
- saved-cart foundation
- Complete-the-Look placement foundation
- configurable incentive messaging

## Exit gate

Free + Pro installs/upgrades safely, Pro can be disabled without corrupting Free data, and conversion-oriented features are proven on FishingClothing.

## Commercial outcome

**First sellable Ultimate Commerce Pro.**

---

# Phase 6 — Rich merchandising, relationships and bundles

**Goal:** create the first major premium moat and replace multiple disconnected merchandising plugins.

## Shared engines

- Product Relationship Graph
- Availability Service
- Campaign Engine

## Bundles

- fixed bundles
- optional/required components
- mix-and-match
- build-your-own
- outfit/Complete-the-Look
- quantity bundle foundation

## Relationships / recommendations

- upsells
- cross-sells
- Frequently Bought Together
- accessories
- alternatives/upgrades
- colour siblings
- stock-aware alternatives

## Smart out-of-stock recovery

- same product/different colour
- alternative variation
- linked sibling
- manually curated substitute
- category/brand fallback
- notify-me fallback

## Merchandising

- dynamic collections
- pin/exclude/boost
- stock/newness/sale rules
- schedules
- automated badges
- sale/offer image overlays
- stock-aware sorting
- deterministic rule explanation

## Notice / announcement system

- multiple/rotating notices
- schedule/campaign association
- page/category/product targeting
- device/customer/cart targeting
- real delivery cut-offs/countdowns where configured

## Campaigns

One campaign can coordinate:

- notices
- badges/overlays
- collection state
- cart messaging
- free-gift presentation
- search merchandising

## Exit gate

Merchants can explain why products/offers appear, control presentation across surfaces and measure basic conversion contribution.

---

# Phase 7 — Gift Cards, Store Credit and Stored Value

**Goal:** add a commercially strong Pro module with year-round and seasonal value.

## Stored Value Engine

- secure ledger-based accounts
- gift cards
- return/store credit
- goodwill credit
- promotional credit foundation
- append-only transactions/reversals
- concurrency/idempotency protections

## Digital Gift Cards

- fixed/custom values
- recipient/sender/message
- branded templates
- scheduled delivery
- customer wallet
- partial redemption
- mixed gift-card/gateway payment
- secure balance checking

## Physical Gift Cards

- inactive card inventory
- activation through Hub
- barcode/QR lookup
- add/redeem/check balance
- replacement/transfer workflow with permissions

## Reporting

- outstanding liability
- sold/redeemed value
- average purchase
- redemption basket value

## Future bulk/corporate foundation

- recipient import
- bulk issue/delivery
- corporate reporting

## Exit gate

Stored-value redemption is concurrency-safe, refund allocation is deterministic, codes are abuse-resistant, and no retry can double-issue/redeem value.

## Commercial outcome

A headline Pro feature with strong Christmas/birthday/returns value.

---

# Phase 8 — Customer Portal, My Size and Stock Intelligence

**Goal:** connect customer preferences, availability and retention.

## Customer Portal

- account overview
- visual order history/timeline
- wishlist/preferences
- gift-card/store-credit wallet
- stock alerts
- recently viewed
- communication preferences
- Buy Again foundation

## My Size / Commerce Profile

- merchant-defined profile attributes
- apparel presets
- preferred-size persistence
- Shop My Size contracts
- consent/privacy controls

## Stock Intelligence

- variation-level back-in-stock alerts
- stock-aware recommendations/merchandising inputs
- velocity signals
- low-stock/high-demand reporting
- days-of-cover foundation

## Exit gate

Customer profile data is exportable/erasable, object-authorised and useful across multiple independent modules.

---

# Phase 9 — Ultimate Commerce Hub foundation

**Goal:** create the staff operational workspace included with Pro, without wp-admin dependency for everyday jobs.

## Hub application

- separate staff-facing route/application surface
- WordPress identity/auth foundation
- Hub-only access option without wp-admin
- mobile-first/PWA-capable shell
- barcode/scanner-friendly search
- role-aware dashboard
- dedicated UC capabilities
- location-scope foundation

## Generic approval framework

- requested
- awaiting approval
- approved/rejected
- executed/cancelled
- audit trail

## Catalog Operations

- create/edit Woo products through CRUD APIs
- simple products
- variable products
- product templates
- guided workflow
- barcode-first lookup/create
- duplicate detection
- variant matrix
- image capture/upload
- supplier metadata foundation
- draft/review/publish permissions
- archive rather than ordinary destructive deletion

## Inventory foundation

- single-location operational model
- Inventory Movement Ledger
- view/adjust/receive stock
- reason codes
- damaged/shrinkage corrections
- audit trail
- basic stocktake
- low-stock notifications

## Exit gate

A non-WordPress-trained staff member can create a product, receive/adjust stock and complete a stock count through Hub without wp-admin, with every mutation authorised/audited.

## Commercial outcome

Pro expands from storefront software into a retail operations platform.

---

# Phase 10 — Multi-location inventory, receiving, transfers and purchasing

**Goal:** support serious retail/warehouse operations while preserving WooCommerce sellable stock truth.

## Locations

- warehouse/store/location model
- operational location balances
- reconciliation strategy to Woo sellable stock
- location-scoped staff permissions

## Transfers

```text
Requested -> Approved -> Picked -> In Transit -> Received -> Closed
```

- partial receipts
- discrepancy handling
- barcode workflows

## Stocktake

- full/cycle/location counts
- blind count option
- resumable sessions
- discrepancy approval

## Receiving

- receive against existing product
- create missing product when permitted
- partial receiving
- damaged/quarantine state
- discrepancy capture

## Suppliers / Purchase Orders

- supplier records
- supplier SKU/cost/lead time
- purchase-order workflow
- inbound quantities
- partial receipt
- approval rules

## Stock intelligence growth

- days of cover
- likely stockouts
- reorder suggestions
- slow/dead stock
- inbound coverage

## Exit gate

Location balances reconcile to WooCommerce according to a documented strategy, and duplicate/retried operational requests cannot silently double-adjust stock.

---

# Phase 11 — Returns, exchanges and tracking maturity

**Goal:** materially reduce customer-service workload and improve post-purchase experience.

## Returns

- eligibility policy engine
- self-service return request
- line-item quantities
- reasons
- audit history
- partial returns
- Hub receipt/inspection workflow
- refund handoff to Woo/payment APIs

## Exchanges

- size/variation exchange
- inventory reservation policy
- race-condition handling
- replacement-order strategy after dedicated architecture review

## Tracking

- normalised shipment/event model
- multiple shipments
- customer timeline
- provider contracts
- production courier adapters
- signed webhook/polling support

## Exit gate

```text
Woo order
 -> shipment
 -> tracking
 -> return request
 -> Hub receipt/inspection
 -> exchange/refund decision
 -> Woo monetary/order handoff
```

with complete ownership and audit controls.

---

# Phase 12 — Automation, alerts and value analytics

**Goal:** connect the platform so modules become more valuable together.

## Rules & Automation Engine

```text
WHEN event/schedule
IF conditions
THEN actions
```

Examples:

- low days-of-cover -> alert purchasing + badge + recommendation adjustment
- campaign starts -> activate collection + notices + overlays + search boost
- repeated fit-return reason -> create insight

## Alert / Notification Engine

- Hub notifications
- email
- push/provider contracts
- Slack/Teams adapters later
- SMS as hosted/usage-billed service where appropriate
- Morning Operations Brief

## Advanced value analytics

- bundle revenue
- recommendation revenue
- OOS alternative conversion
- gift-card performance
- back-in-stock conversion
- return/fit insight
- stock movement/shrinkage
- campaign/collection performance

## Exit gate

Automations are idempotent/auditable, do not create uncontrolled recursion, and value attribution is documented rather than overstated.

---

# Phase 13 — Agency / enterprise hardening

**Goal:** make UC credible on major stores and repeatable agency estates.

## Deliverables

- WP-CLI operational commands
- mature config deployment/import/export
- expanded capability/role presets
- optional enterprise SSO/SAML integration surface
- performance benchmark suite
- 100k+ product / large operational fixtures where representative
- object-cache/CDN guidance
- provider-outage/failure-injection tests
- migration interruption/recovery tests
- reconciliation tooling
- formal compatibility matrix
- security incident-response process
- independent penetration/security review
- SBOM/release provenance where useful
- agency docs/reference implementations
- audit retention controls
- advanced governance/approval options

## Exit gate

Independent security findings are resolved and documented scale/upgrade targets are proven on realistic data.

---

# Phase 14 — Optional hosted services

**Goal:** add infrastructure-backed services only when they create value local WordPress code cannot deliver economically/reliably.

Potential services:

- hosted search
- AI/ML recommendations
- behavioural modelling
- communication delivery
- image/data enrichment
- large-scale analytics

## Rules

- separate service entitlement/usage from ordinary Pro licence
- explicit merchant data/consent contracts
- graceful degradation when unavailable
- no non-essential hosted service may become a checkout single point of failure
- transparent limits/costs

---

# Immediate build priority

The immediate sequence remains:

1. finish Phase 0 product split/distribution architecture
2. land Phase 1 security + WordPress.org CI foundations
3. build Phase 2 Free catalogue/variant/cart vertical slice and prove it on FishingClothing
4. make Free complete enough to stand alone
5. submit/launch Free on WordPress.org
6. ship the first sellable Pro conversion release
7. build merchandising/relationships/bundles as the first major premium moat

Gift Cards, Customer Portal and Hub can then be pulled forward according to commercial timing and merchant demand without violating the underlying shared-engine architecture.

---

# North-star release test

Before prioritising a roadmap item, ask:

> Does this materially increase adoption, merchant revenue, customer retention, operational efficiency, security, scalability or platform defensibility?

If not, it should not displace work that does.
