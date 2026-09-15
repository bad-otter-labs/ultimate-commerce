# Ultimate Commerce Product Blueprint

Status: **Commercial, distribution, security and product-tier source of truth**

This document defines how Ultimate Commerce is packaged, distributed, secured and commercialised. It complements `FOUNDATION.md`, which remains the source of truth for WooCommerce ownership, reusable commerce architecture and module/domain boundaries.

Where an older statement in `FOUNDATION.md`, `BUILD-WORKFLOW.md` or `AGENTS.md` conflicts with this blueprint on **Free/Pro packaging, WordPress.org distribution, managed updates or product naming**, this blueprint wins and the conflicting document must be reconciled in the same change set.

## 1. Product mission

Ultimate Commerce exists to make WooCommerce feel like a much more capable modern retail platform without replacing WooCommerce as the commerce system of record.

The commercial proposition is:

> **Ultimate Commerce is the commerce experience layer for WooCommerce.**

WooCommerce owns transaction truth. Ultimate Commerce improves discovery, product selection, merchandising, conversion, customer experience, aftercare and retention.

The platform should be valuable to independent stores, agencies and large merchants. The first implementation on FishingClothing.co.uk is a proving ground, not a privileged code path.

## 2. Product family and naming

The product family is intentionally split.

| Purpose | Product identity |
| --- | --- |
| Brand | **Ultimate Commerce** |
| WordPress.org Free plugin | **Ultimate Commerce for WooCommerce** |
| Planned WordPress.org slug | `ultimate-commerce-for-woocommerce` |
| Paid companion | **Ultimate Commerce Pro** |
| Planned Pro plugin slug | `ultimate-commerce-pro` |
| Vendor | **Bad Otter Labs** |
| Canonical PHP namespace | `BadOtter\UltimateCommerce` |
| Canonical storage/API prefix | `uc_` / `ultimate-commerce/v1` |
| Internal Bad Otter product identity | `ultimate-commerce` |
| Marketing shorthand | **UC** |

The planned WordPress.org slug becomes final only once accepted by WordPress.org. Until then, do not create public promises that rely on the directory URL being reserved.

Use the official spelling **WooCommerce** in names, metadata and marketing.

## 3. Repository and dependency topology

Ultimate Commerce Free and Ultimate Commerce Pro are separate installable plugins and should ultimately live in separate repositories.

```text
WordPress
  -> WooCommerce
      -> Ultimate Commerce for WooCommerce (Free)
          -> Ultimate Commerce Pro (optional)
              -> Store implementation / theme / site plugin
```

Target repository model:

- `bad-otter-labs/ultimate-commerce` — Free product; intended to become public before WordPress.org submission.
- `bad-otter-labs/ultimate-commerce-pro` — private paid companion.
- merchant/store repositories — presentation and store-specific configuration only.

Hard rules:

1. Free must never depend on Pro.
2. Pro may depend on documented public contracts exposed by Free.
3. Pro must not receive secret privileged APIs unavailable to legitimate third-party extensions.
4. A store must not copy or fork Free/Pro implementation code.
5. FishingClothing-specific behaviour remains outside both UC products.

The Free plugin must be a meaningful standalone product. It must not be an empty framework, installer, advert or crippled shell for Pro.

## 4. Distribution model

### Ultimate Commerce for WooCommerce — Free

Public Free releases are distributed through **WordPress.org** and updated through the standard WordPress.org plugin update channel.

The WordPress.org build must contain:

- no Bad Otter custom plugin updater
- no external update override
- no premium entitlement code required for Free features
- no premium implementation hidden behind licence checks
- no remote-loaded executable code
- no silent telemetry or activation ping

Private development/QA builds may be distributed internally before WordPress.org approval, but that temporary pre-release mechanism must not leak into the WordPress.org package.

### Ultimate Commerce Pro

Pro is distributed by Bad Otter Labs and updated through the Bad Otter managed WordPress update system.

Pro may use:

- Bad Otter licence/entitlement services
- Bad Otter OIDC release publishing
- Bad Otter package inspection and checksums
- native WordPress Plugins update UI through the Bad Otter updater

### FishingClothing Core

FishingClothing Core remains a separate store-specific Bad Otter plugin. It is not part of the public Ultimate Commerce package and must never be bundled into Free or Pro.

## 5. Free product promise

Free should make a normal WooCommerce store noticeably better immediately.

It must be good enough that a merchant can install it, use it indefinitely and recommend it without buying Pro.

Target Free capabilities:

### Platform foundation

- dependency/compatibility checks
- modular registry and public extension contracts
- HPOS compatibility
- Cart/Checkout Blocks compatibility where relevant
- diagnostics and system status
- accessible UI primitives
- settings import/export excluding secrets
- public analytics/event vocabulary without mandatory external telemetry

### Catalogue and product experience

- reusable product-card/view-model system
- configurable card components
- basic text/button variation selectors
- colour/image swatches
- selected-variation state
- variation availability states
- variation-aware images where supported
- basic low-stock presentation
- manual product badges
- basic quick add

### Cart and retention basics

- AJAX/Store API cart interactions
- clean basic cart drawer
- quantity/remove controls
- basic wishlist
- recently viewed products
- basic account/order presentation hooks

Free should remain fast, accessible and theme-independent. It should not require a Bad Otter account or external service to operate its normal local features.

## 6. Pro product promise

Pro exists to create measurable merchant value through increased conversion, stronger merchandising, customer retention and reduced operational workload.

The paid proposition should be described as:

> **Sell more. Merchandise better. Retain customers. Reduce admin.**

Target Pro module families:

### Advanced Variants & Product Discovery

- linked colour/sibling products
- colour-family grouping
- colour-specific URLs where appropriate
- size availability on listing cards
- preferred-size persistence
- `Available in my size` filtering/contracts
- advanced quick-add
- variation galleries
- stock-aware substitution/fallbacks
- advanced unavailable-combination handling

### Conversion & Advanced Cart

- cart recommendations
- frequently bought together
- complete-the-look
- free-delivery progress rules
- configurable incentives
- cross-sell orchestration
- saved cart
- advanced cart merchandising blocks
- abandoned-cart integration hooks

### Merchandising Engine

- dynamic collections
- rule-based product groups
- pin/exclude/boost controls
- scheduled collections and campaigns
- automated badges
- stock-aware ranking
- newness/bestseller rules
- conversion/sales-velocity inputs where available
- margin-aware ranking where a safe merchant-owned data source exists
- deterministic inspectable rule explanations

### Recommendations

- curated recommendations
- same-brand/same-collection strategies
- complementary category rules
- frequently bought together
- recently viewed/trending strategies
- fallback chains
- future behavioural/ML providers behind the same contracts

### Customer Portal

- modern account overview
- visual order history/timeline
- shipment tracking presentation
- return/exchange entry points
- advanced wishlist experiences
- saved preferences/sizes
- stock alerts
- recently viewed
- communication preferences

### My Size / Commerce Profile

- saved top/trouser/footwear sizes or merchant-defined profile attributes
- fit preference
- preferred-size filtering
- brand/product fit intelligence over time
- future recommendation confidence using consented merchant-owned data

### Returns & Exchanges

- self-service returns
- configurable eligibility rules
- reason codes
- partial returns
- exchanges for size/variation
- inventory reservation policy support
- return labels/QR provider contracts
- refund handoff to Woo/payment APIs
- audit trail and return analytics

### Tracking

- provider-neutral shipment model
- multiple shipments
- normalised courier events
- tracking timeline
- delivery/failure states
- courier adapters
- customer notifications through provider contracts

### Stock Intelligence

- variation-level back-in-stock subscriptions
- stock-aware recommendations and collections
- demand/velocity signals
- dead-stock/high-demand indicators
- replenishment-oriented insights where reliable data exists

### Promotions & Automation

- promotion presentation rules
- spend/category/brand/customer messaging
- scheduled campaign presentation
- free-gift/bundle orchestration only where compatible with the authoritative pricing engine
- automation hooks and workflows

### Advanced Analytics & Value Attribution

- recommendation-attributed revenue
- merchandising performance
- back-in-stock conversion
- return/exchange rates
- fit/return reason insights
- module value reporting

Pro should remain one paid product initially rather than a confusing marketplace of many paid micro-addons. Internally, Pro modules stay isolated so future packaging can evolve without a rewrite.

## 7. Free vs Pro rule

The split is governed by merchant value, not artificial inconvenience.

Free primarily contains foundational storefront improvements and useful baseline tools.

Pro primarily contains capabilities that:

- directly improve revenue/conversion in sophisticated ways
- automate substantial merchant work
- require complex workflow/state management
- provide advanced merchandising intelligence
- integrate paid/external providers
- create cross-module operational value

Never remove a healthy Free feature merely to force upgrades.

Never ship paid implementation code inside Free and unlock it with a licence flag.

## 8. Hosted services are a separate commercial layer

Some future features have real ongoing infrastructure cost and must not be hidden inside an unlimited annual Pro licence.

Potential hosted services include:

- hosted search/indexing
- AI/ML recommendation inference
- large-scale behavioural analytics
- SMS/email delivery
- image enrichment/processing
- hosted feeds or data transformation

Hosted services may use usage allowances, metered billing or higher service tiers.

Local Pro functionality and hosted-service functionality must be distinguishable so a merchant can understand what runs on their WordPress installation and what depends on Bad Otter infrastructure.

## 9. Licensing principles

Initial pricing hypothesis, subject to market validation:

| Licence | Annual target |
| --- | ---: |
| Single production site | £149–£199 initially |
| 5 sites | £299–£349 |
| 25 sites / Agency | £599–£699 |

As the mature Pro product adds returns, exchanges, tracking, merchandising, sizing and analytics, a single-site price around £249/year may be justified.

Commercial rules:

- no lifetime licence at launch
- local/staging/development environments should not consume paid production-site limits where they can be reliably identified
- licence expiry must never delete or conceal merchant/customer data
- installed local Pro functionality should fail safely and should not intentionally break the storefront when a subscription expires
- updates/support and paid hosted services may stop when entitlement expires
- data remains readable/exportable subject to normal privacy permissions
- no dark-pattern admin nags

## 10. Agency and enterprise strategy

Agencies are a first-class distribution channel.

Ultimate Commerce should support:

- predictable hooks/contracts
- configuration export/import
- staging-safe licences
- semantic CSS/DOM contracts where UI is supplied
- no theme lock-in
- public developer documentation
- WP-CLI support over time
- repeatable deployment across multiple stores
- clear compatibility matrices

Large merchants additionally require:

- least-privilege capabilities
- strong audit trails
- performance at large catalogue/order volumes
- provider failure isolation
- observability
- predictable migrations
- security disclosure and response processes

## 11. Long-term moat

Basic swatches, wishlists and cart drawers are acquisition features, not the long-term moat.

The defensible system is the connected intelligence between:

- merchandising
- customer preferences and sizing
- stock availability
- recommendations
- returns/exchanges
- post-purchase experience

Example:

```text
saved size
 -> product availability
 -> recommendation ranking
 -> purchase
 -> return reason / fit outcome
 -> fit guidance
 -> future recommendation and merchandising quality
```

Architecture principle:

> **Ultimate Commerce modules should become more valuable when used together while remaining independently useful.**

## 12. Security architecture contract

Ultimate Commerce must be designed as if it may run on high-volume, high-value stores.

Core rule:

> **Every request is untrusted. Every sensitive identifier requires authorisation. Every external system can fail or be hostile.**

### 12.1 Authentication and authorisation

- use WordPress/WooCommerce authentication rather than inventing a password/session system
- create dedicated least-privilege UC capabilities instead of scattering `manage_options`
- enforce object-level ownership for customer orders, returns, wishlists, profiles and tracking resources
- never treat UI visibility as authorisation
- never treat a valid nonce as authorisation

Expected capabilities include concepts such as:

- `uc_manage_settings`
- `uc_manage_merchandising`
- `uc_manage_returns`
- `uc_manage_integrations`
- `uc_view_analytics`
- `uc_manage_promotions`

### 12.2 REST/AJAX request security

Every UC state-changing route/action requires, as applicable:

1. authentication or a purpose-built guest token
2. CSRF protection
3. capability/ownership verification
4. strict request schema
5. validation and sanitisation
6. bounded input sizes/ranges
7. safe persistence
8. context-correct output escaping

REST routes require explicit `permission_callback` logic. Request schemas must prevent mass assignment of privileged properties such as user IDs, refund totals, internal statuses or admin notes.

### 12.3 Data and database security

- prefer WooCommerce CRUD/public APIs for Woo-owned data
- use prepared WordPress database APIs for UC storage
- design indexes and bounded queries before high-volume tables ship
- no arbitrary `limit=-1` public endpoints
- never clone sensitive Woo data into UC merely for convenience

### 12.4 Webhooks

Inbound provider webhooks should use a shared UC webhook security layer supporting:

- HMAC/signature verification
- timestamp tolerance
- constant-time comparisons
- replay protection
- event allowlists
- body-size limits
- idempotency/deduplication
- safe retry semantics

IP allowlisting alone is never sufficient authentication.

### 12.5 Outbound HTTP and SSRF

- use WordPress HTTP APIs
- keep TLS verification enabled
- provider adapters should use known service endpoints where possible
- user-configurable URLs require scheme/host validation and SSRF-safe handling
- arbitrary internal/private-network fetching must not be exposed to untrusted users

### 12.6 Secrets

Provider credentials and webhook secrets must use a narrow secret-store abstraction.

Secrets must never appear in:

- frontend JavaScript
- REST responses
- diagnostics exports
- normal application logs
- configuration exports
- source control

Support environment/`wp-config.php` supplied encryption material for higher-security installations where practical.

### 12.7 Payments and PCI boundary

Ultimate Commerce does not handle raw card numbers, CVV or equivalent card-authentication data.

Payments remain the responsibility of WooCommerce payment gateways. UC consumes safe payment/order state only.

### 12.8 Guest workflows

Guest tracking/returns must not rely on predictable values alone.

Use high-entropy purpose-bound expiring tokens, safe token storage, rate limiting and explicit revocation/one-time semantics where suitable.

### 12.9 Abuse and denial-of-service resistance

- enforce pagination and maximum page sizes
- bound date/query ranges
- rate-limit expensive guest/public workflows where necessary
- do not permit customer-controlled background-job fan-out
- use bounded provider retries/backoff
- cache/materialise expensive derived data with explicit invalidation

### 12.10 Background jobs

Action Scheduler jobs should generally store identifiers rather than unnecessary PII snapshots or credentials.

Jobs must be idempotent where practical, have bounded retries and expose failed/dead work to diagnostics.

### 12.11 Privacy

Classify UC-owned data as:

- public
- merchant operational
- personal
- credential/secret

Integrate UC-owned personal data with WordPress privacy exporter/eraser mechanisms where appropriate. Store only the personal data required for the feature and define retention behaviour for high-volume event/log data.

### 12.12 Logging and audit

Operational logs must redact secrets and unnecessary personal information.

High-impact actions should create audit records with actor, timestamp, target and reason/context where appropriate, including:

- return eligibility/status overrides
- exchange overrides
- refund initiation/handoff
- integration credential changes
- merchandising/promotion publication where material

### 12.13 Supply-chain and release security

- lock dependencies
- scan Composer/npm dependencies
- reject committed secrets
- package only required production files
- use reproducible release builds where practical
- retain source/build provenance
- generate/check checksums for Pro releases
- consider SBOM generation before enterprise launch
- require independent security review before positioning the product for major enterprise stores

## 13. WordPress.org distribution contract for Free

Ultimate Commerce Free is designed for directory compliance from the first line of product code.

Hard rules for the WordPress.org package:

- Free is useful without Pro
- no external custom updater
- no automatic installation/downloading of Pro from a third-party server
- no premium implementation hidden in Free behind payment/licence checks
- no remote executable PHP/JS or remotely hosted static assets that are not genuinely part of an external service
- no unsolicited tracking, activation ping or hidden telemetry
- telemetry/diagnostics collection beyond what is technically essential must be clearly explained and opt-in where required
- use WordPress/WooCommerce public APIs and the WordPress HTTP API
- use WordPress-provided libraries where appropriate instead of bundling unnecessary duplicates
- ship/localise assets required for normal Free functionality
- make human-readable source and build instructions available for compiled/minified assets
- keep plugin headers, stable tag and version metadata aligned
- use translation-ready strings and standard escaping/sanitisation practices
- keep upsell UI restrained and useful rather than turning wp-admin into advertising
- destructive uninstall is merchant-controlled; deactivation never destroys business/customer data

Before directory submission, the Free repository should be public and reviewable unless a deliberate exception is accepted by the WordPress.org team.

## 14. WordPress.org release gate

No Free release intended for WordPress.org is complete until CI passes the directory-focused gate.

Required checks should include:

- official Plugin Check with no blocking repository/security errors
- WordPress Coding Standards / PHPCS
- PHP syntax and compatibility checks
- static analysis
- dependency vulnerability audits
- dependency/licence review
- secret scanning
- unit/integration tests
- critical end-to-end flows
- supported WordPress/WooCommerce matrix
- HPOS tests
- Cart/Checkout Blocks tests for affected modules
- accessibility checks for core UI interactions
- package inspection for development junk, credentials, nested ZIPs or forbidden files
- version/header/readme alignment
- tests covering permissions, object ownership, CSRF, XSS, SQL injection boundaries, SSRF boundaries and privilege escalation

WordPress.org approval remains an external review decision; passing CI is necessary but not a guarantee of acceptance.

## 15. Pro release gate

Pro uses the Bad Otter release pipeline.

A Pro release requires:

```text
source validated
 -> deterministic/canonical package built
 -> Bad Otter draft candidate
 -> exact package promoted to Stable
 -> live WordPress managed-update lookup verified
 -> downloaded package checksum/byte identity verified
 -> immutable Git tag/GitHub Release
```

Long-lived publisher credentials are prohibited; use the organisation's short-lived GitHub OIDC release identity.

## 16. Performance and scale contract

Ultimate Commerce must be designed for stores far larger than the first implementation.

Release/testing fixtures must eventually cover:

- thousands/tens of thousands of products
- large variable-product catalogues
- high order/customer counts
- large wishlists/alerts/returns datasets
- sustained background queues

Hard performance rules:

- no N+1 queries across catalogue grids
- no unnecessary module assets/work
- no blocking remote calls in critical shopper paths where avoidable
- indexes follow real access patterns
- batch/queue bulk work
- pagination is mandatory for large collections
- cache expensive deterministic derivations
- module performance budgets are measured, not assumed

## 17. Compatibility contract

The supported matrix is explicit and tested.

Priority compatibility areas:

- modern supported WordPress
- modern supported WooCommerce
- HPOS
- Cart/Checkout Blocks
- common caching/object-cache stacks
- multilingual stores
- multi-currency ecosystems
- major themes/page-builders only where a documented compatibility claim is made

Compatibility claims follow tests and evidence, not marketing wishes.

## 18. Data portability and merchant ownership

Merchant/customer data is never held hostage to a licence.

UC-owned concepts should have safe export/import or WordPress privacy/export paths where relevant, including:

- configuration
- merchandising rules
- return/exchange records
- customer profiles/sizes
- wishlists
- stock alerts
- tracking events

Secrets are excluded from exports.

If a module is disabled or Pro entitlement ends, retained data must remain safe and recoverable according to permissions and privacy policy.

## 19. Analytics and commercial proof

Pro renewal should be supported by measurable value rather than fear of losing functionality.

Where privacy/consent permits, the platform should be able to report merchant-owned metrics such as:

- revenue influenced by recommendations
- conversion from back-in-stock alerts
- dynamic-collection performance
- cart cross-sell contribution
- return/exchange rates and reasons
- fit-related outcomes
- stock/merchandising effectiveness

Analytics contracts must remain provider-neutral and must not leak customer data to Bad Otter by default.

## 20. Admin and upsell principles

The admin experience should feel like a professional commerce product.

- Free and Pro module status is clear
- paid modules may be discoverable from Free without intrusive nags
- no full-screen takeover advertising
- no repeated notices on unrelated admin screens
- diagnostics expose useful health information without secrets
- upgrade messaging explains merchant value, not artificial limitations

## 21. Product success definition

Ultimate Commerce succeeds commercially when:

1. Free earns adoption because it materially improves WooCommerce.
2. Pro converts because advanced modules produce clear ROI or save meaningful operational time.
3. agencies can standardise on the platform without forks.
4. large merchants can trust its security, performance, upgrades and data ownership.
5. the connected module data creates increasing value across merchandising, sizing, stock, recommendations and aftercare.
6. FishingClothing.co.uk proves real-world quality without contaminating the generic platform with store-specific logic.
