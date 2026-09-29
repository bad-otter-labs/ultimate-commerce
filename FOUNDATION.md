# Ultimate Commerce Founding Architecture

Status: **Founding technical and engineering constitution**

This document is the source of truth for Ultimate Commerce technical architecture, WooCommerce ownership boundaries, reusable domain rules and engineering principles.

`BLUEPRINT.md` is the source of truth for product family, Free/Pro packaging, WordPress.org distribution, security/commercial policy and product naming. `ROADMAP.md` defines implementation sequence. Where an older statement in this document conflicts with `BLUEPRINT.md` on those product/distribution subjects, `BLUEPRINT.md` wins and this document must be reconciled deliberately.

## 1. Mission

Ultimate Commerce is a reusable WooCommerce enhancement platform.

Its job is to turn a standard WooCommerce installation into a significantly stronger retail platform while preserving WooCommerce as the underlying commerce engine and system of record.

Ultimate Commerce must be capable of serving many stores and retail verticals. It may provide especially strong capabilities for apparel, but no store, brand, domain, supplier, courier, taxonomy or return policy may be hard-coded into the platform.

The first store using Ultimate Commerce is a proving ground, not a privileged code path.

## 2. Repository authority and hard boundary

This repository is the sole source of truth for reusable **Ultimate Commerce Free** behaviour and the public contracts consumed by stores, Pro and third-party extensions.

Belongs here:

- reusable product and catalogue enhancements
- product-card data and behaviour
- baseline variation and swatch behaviour
- baseline quick add and cart drawer behaviour
- baseline account/customer experience contracts
- wishlist/recently-viewed foundations
- baseline stock presentation
- analytics/event contracts
- integration adapter contracts and public interfaces
- commerce admin tooling and diagnostics
- background commerce job infrastructure
- reusable WooCommerce interoperability code
- extension points used by Ultimate Commerce Pro

Reusable paid implementation belongs in the separate Ultimate Commerce Pro product/repository once that product is established.

Does not belong here:

- brand colours, fonts or visual identity
- a store domain name
- store marketing content
- store-specific navigation
- store-specific taxonomies or labels
- one store's delivery threshold
- one store's return window
- one store's return reasons
- supplier-specific imports that exist for only one merchant
- credentials or secrets
- bespoke ERP mappings for one merchant
- conditionals based on a customer/store name
- paid Pro implementation hidden behind a licence check inside Free

There must never be code such as `if ( $store === 'some-client' )` in Ultimate Commerce.

Store-specific requirements should become either:

1. reusable capabilities exposed through configuration/contracts, or
2. code that remains in the store repository.

## 3. Dependency model

The intended dependency direction is:

```text
WordPress
    -> WooCommerce
        -> Ultimate Commerce for WooCommerce (Free)
            -> Ultimate Commerce Pro (optional)
                -> Store implementation / theme / site plugin
```

Free must never depend on Pro.

Pro may depend on documented public Free contracts.

A store may depend on Ultimate Commerce capabilities but must never require a fork.

Ultimate Commerce must also work without any proprietary presentation framework. A store may style/render Ultimate Commerce using its own theme. UC therefore exposes semantic data, state, hooks, APIs, templates/slots and minimal functional UI rather than requiring a specific design system.

## 4. Fundamental platform rules

1. WooCommerce owns commerce truth.
2. Ultimate Commerce enhances commerce; it does not replace WooCommerce.
3. Stores configure capabilities; they do not fork Ultimate Commerce.
4. Public WooCommerce and WordPress APIs are preferred over internal implementation details.
5. Never duplicate authoritative WooCommerce data without a compelling reason.
6. Modules own their business logic and data.
7. External services are accessed through adapters/contracts.
8. Background work must not block shopper requests when it can be queued.
9. Disabled modules should not load unnecessary frontend assets or execute unnecessary queries.
10. Performance, accessibility, security, observability and upgradeability are product features.
11. Every enhancement should materially improve buying, servicing, merchandising or operating a store.
12. Pro extends Free through public contracts; Free is never a crippled loader for Pro.
13. Ultimate Commerce modules should become more valuable when used together while remaining independently useful.

## 5. WooCommerce ownership vs Ultimate Commerce ownership

### WooCommerce owns

- products and variations
- product prices
- product stock quantities and stock status
- tax classes and tax calculation
- coupons where native Woo coupons are used
- carts and checkout totals
- customer accounts and identities at the Woo/WordPress layer
- orders
- order line items
- payments and payment-method integration state
- refunds at the Woo order/payment layer
- shipping methods/rates where supplied by Woo shipping integrations

Ultimate Commerce must use WooCommerce CRUD objects and public APIs for these concepts.

Order code must be HPOS-safe. Ultimate Commerce must not assume orders are WordPress posts or query order truth directly from `wp_posts` / `wp_postmeta`.

### Ultimate Commerce may own

- return/RMA records and return-item workflow state
- exchange workflow state
- wishlist collections and wishlist items
- stock-alert subscriptions
- customer commerce-profile preferences such as saved sizes
- normalised carrier tracking events
- merchandising rules and curated collection rules
- recommendation rules/cache
- promotion presentation metadata where it is not Woo-native commerce truth
- analytics/event journal data where retained locally
- provider/integration settings
- module configuration
- migration/schema state

Where an Ultimate Commerce record refers to a Woo object, it stores the Woo identifier rather than cloning the Woo object.

## 6. Product and plugin identity

Brand:

`Ultimate Commerce`

Public Free plugin name:

`Ultimate Commerce for WooCommerce`

Planned WordPress.org slug (subject to approval):

`ultimate-commerce-for-woocommerce`

Paid companion:

`Ultimate Commerce Pro`

Planned Pro slug:

`ultimate-commerce-pro`

Internal Bad Otter product identity may remain:

`ultimate-commerce`

Canonical PHP namespace:

`BadOtter\UltimateCommerce`

Canonical option/table prefix:

`ulticofo_`

The pre-WordPress.org `uc_` prefix is legacy-only and may be read or removed solely for migration/cleanup compatibility.

Canonical REST namespace for genuinely UC-owned routes:

`ultimate-commerce/v1`

Use Woo Store API extension mechanisms when UC is enriching supported Store API resources. Do not create a duplicate cart, product or checkout API merely to avoid integrating with WooCommerce.

## 7. Modular architecture and product tiers

Ultimate Commerce Free is an installable plugin with independently activatable modules behind a central module registry.

Ultimate Commerce Pro is a separate installable plugin that requires Free and registers additional modules through documented public Free contracts.

A module must declare at minimum:

- identifier
- human-readable name
- owning product/tier
- dependencies
- compatibility requirements
- settings schema where applicable
- boot lifecycle
- frontend/admin asset requirements

Disabling a module must remove its shopper/admin behaviour cleanly and, where practical, prevent its assets and jobs from loading.

Expected Free modules include:

### Free storefront/platform

- Catalogue / Product View Models
- Product Cards
- Basic Variants & Swatches
- Quick Add
- Basic Cart Drawer
- Basic Wishlist
- Recently Viewed
- Basic Stock Presentation
- Basic Account/Order Contracts
- Analytics Event Contracts
- Integration Registry Contracts
- Scheduled Jobs Infrastructure
- Diagnostics
- Import/Export of safe configuration

Expected Pro module families include:

### Pro conversion and discovery

- Advanced Variants / Linked Colours
- Advanced Cart & Conversion
- Advanced Checkout Enhancements

### Pro customer lifecycle

- Advanced Customer Portal
- My Size / Saved Commerce Profile
- Back-in-stock / Stock Intelligence

### Pro aftercare

- Returns
- Exchanges
- Tracking

### Pro merchandising

- Dynamic Collections
- Promotions
- Recommendations
- Product Relationships / Complete the Look
- Advanced Analytics / Value Attribution

A module may expose extension points to other modules, but circular module dependencies are prohibited.

Pro must not rely on secret privileged APIs unavailable to legitimate extensions.

## 8. Proposed code layout

The Free repository should evolve toward a structure conceptually similar to:

```text
ultimate-commerce/
├── ultimate-commerce-for-woocommerce.php
├── composer.json
├── package.json
├── src/
│   ├── Bootstrap/
│   ├── Contracts/
│   ├── Infrastructure/
│   ├── Security/
│   ├── Support/
│   └── WooCommerce/
├── modules/
│   ├── catalogue/
│   ├── product-cards/
│   ├── variants/
│   ├── quick-add/
│   ├── cart/
│   ├── wishlist/
│   ├── recently-viewed/
│   ├── stock/
│   ├── account/
│   └── analytics/
├── integrations/
├── assets/
├── templates/
├── tests/
└── docs/
```

The Pro repository should mirror the same domain discipline rather than becoming a monolith:

```text
ultimate-commerce-pro/
├── ultimate-commerce-pro.php
├── modules/
│   ├── advanced-variants/
│   ├── advanced-cart/
│   ├── merchandising/
│   ├── recommendations/
│   ├── customer-portal/
│   ├── my-size/
│   ├── stock-intelligence/
│   ├── returns/
│   ├── exchanges/
│   ├── tracking/
│   ├── promotions/
│   └── analytics/
└── integrations/
```

The final structures may evolve, but domain and product boundaries must remain explicit.

## 9. Product and catalogue principles

Ultimate Commerce must not define one universal retail taxonomy.

It should provide reusable concepts such as:

- attribute groups
- display attributes
- swatches
- variation selectors
- technical/feature attributes
- product badges
- product relationships
- merchandising metadata
- product-card component configuration

A store may configure attributes such as size, colour, fit, waterproof rating or material, while another store may configure voltage, capacity, compatibility or finish.

Ultimate Commerce may ship optional vertical presets, such as Apparel, but presets are configuration templates rather than special runtime branches.

## 10. Product-card contract

There must be one reusable product-card data/behaviour system rather than unrelated templates implementing the same logic repeatedly.

Supported conceptual components may include:

- media
- secondary/hover media
- brand/manufacturer label
- title
- subtitle
- current price
- previous price
- saving
- badges
- rating
- variation swatches
- size/option availability
- low-stock state
- quick add
- wishlist
- comparison or future actions

Stores control which components are enabled and how they are presented.

The product card must not perform N+1 queries across a listing grid.

## 11. Variation principles

The variation layer should support:

- text/button selectors
- colour/image swatches
- variation-specific imagery
- stock-aware options
- unavailable/disabled options
- low-stock messaging
- deep-linking to selected variants where appropriate
- variation-aware quick add
- precise variation stock-alert contracts
- optional linked-product strategies for catalogues that cannot be represented cleanly by standard variations

Basic variation UX belongs in Free. Advanced linked-colour, preferred-size and listing-level availability intelligence may belong in Pro under `BLUEPRINT.md`.

WooCommerce remains authoritative for variation price and stock.

## 12. Cart and checkout

Ultimate Commerce must use WooCommerce cart and checkout calculations rather than creating a parallel cart engine.

Shopper enhancements may include:

- asynchronous add/remove/update actions
- cart drawer
- free-delivery progress presentation
- promotion messages
- cross-sells
- product recommendations
- variation editing
- compact coupon treatment
- delivery promise messaging
- express-payment presentation
- configurable checkout fields

Baseline cart interactions should be useful in Free. Advanced conversion/merchandising behaviour may be Pro.

Use Woo Store API and supported block extensibility where suitable. Custom UC REST routes should be created only for UC-owned concepts or workflows that do not belong in Store API schemas.

Ultimate Commerce does not process card data and does not implement a proprietary payment processor.

## 13. Customer account

The account experience may enhance Woo's customer/order foundation with reusable sections including:

- overview
- current/recent orders
- order detail
- tracking timeline
- return/exchange actions
- wishlist
- saved commerce profile
- stock alerts
- addresses
- payment methods provided by installed gateways
- communication preferences

Free may expose baseline account/order contracts and presentation hooks. Advanced portal, profiles, aftercare and intelligence may be Pro.

Account modules must enforce ownership checks server-side. UI visibility is never authorisation.

Guest-order association to a later account may be supported only through a deliberate verified process.

## 14. Returns and exchanges

Returns are a first-class workflow, not a boolean flag on an order.

A return/RMA should be capable of recording:

- owning order
- returned line items and quantities
- requested resolution
- reason codes
- free-text detail where enabled
- eligibility result and rule version
- customer/store timestamps
- return method
- label/QR/provider reference
- parcel tracking
- inspection state
- refund/replacement/exchange outcome
- actor and audit history

Generic workflow states should be explicit and never represented through deletion.

Return eligibility rules must be configurable. Ultimate Commerce supplies the engine; each store supplies its policy values.

Exchange workflows must consider inventory reservation and race conditions. An exchange option presented as available must not imply a permanent reservation unless the store policy explicitly reserves that stock.

Ultimate Commerce must ultimately call Woo/payment APIs to execute monetary refunds rather than maintaining a second refund ledger.

Returns/exchanges are expected Pro capabilities because they automate substantial merchant workflow.

## 15. Tracking

Carrier/provider statuses should be normalised into a stable UC vocabulary such as:

- created
- packed
- dispatched
- in_transit
- out_for_delivery
- delivered
- delivery_failed
- returned_to_sender
- returned

Provider-specific payloads may be retained for diagnostics, but storefront code should consume the normalised model.

Tracking providers implement a common contract. No carrier-specific logic belongs in generic order/account templates.

Tracking is expected to be a Pro capability, with provider adapters behind explicit contracts.

## 16. Search

Ultimate Commerce must define a search-provider contract rather than couple storefront behaviour to a particular search vendor.

A provider may be native WordPress/Woo search, OpenSearch/Elasticsearch, Algolia, Meilisearch or another future implementation.

Search requests should be capable of expressing:

- text query
- taxonomy/attribute filters
- range filters
- stock/availability constraints
- sorting
- pagination
- facets

Search responses should expose a stable UC result/facet model to the store layer.

Hosted search may become a separate service rather than being silently subsidised by the ordinary Pro licence.

## 17. Merchandising

Dynamic collections should support rule-based inclusion and manual curation.

Examples of generic rules:

- taxonomy/attribute membership
- price range
- stock state
- publication/newness date
- sales velocity where data is available
- product relationship
- sale state

Merchants should be able to pin, exclude and order products without altering the underlying catalogue taxonomy solely for presentation.

Merchandising logic must remain deterministic and inspectable.

Advanced merchandising is expected to be a key Pro differentiator.

## 18. Promotions

Ultimate Commerce may enhance promotion discovery and messaging, but it must distinguish between:

- pricing/discount truth, and
- promotion presentation.

Where WooCommerce or another pricing engine owns the actual discount, UC should display/explain that result rather than recalculate a competing total.

If UC introduces its own reusable promotion engine later, it requires a separate architecture decision covering calculation priority, tax, refunds, stacking, coupons, caching and checkout compatibility.

## 19. Recommendations

Recommendations begin rules-first and deterministic.

Possible strategies include:

- related collection
- complementary taxonomy
- product relationship
- frequently bought together derived from order data
- recently viewed
- manually curated

A future behavioural/ML provider must fit behind the recommendation contract. Frontend components must not care how recommendations were produced.

Rules/local recommendation capability may be Pro; infrastructure-heavy hosted ML is a separate hosted-service decision.

## 20. Integration contracts

External systems must sit behind explicit contracts. Expected contract families include:

- `TrackingProvider`
- `ReturnProvider`
- `SearchProvider`
- `NotificationProvider`
- `AnalyticsProvider`
- `RecommendationProvider`

Provider adapters translate external vocabulary and failures into UC-owned result types.

Credentials use a narrow protected secret/configuration abstraction and must never be committed to Git or exposed through normal diagnostics.

Provider failure must degrade safely. A tracking outage must not prevent checkout. An email timeout must not corrupt an order. Retryable work should be queued.

## 21. Background jobs

Long-running or retryable work should use Action Scheduler/Woo-supported scheduled action infrastructure unless a later architecture decision establishes a different queue.

Examples:

- tracking polling
- stock-alert delivery
- search indexing
- recommendation materialisation
- return-provider calls
- bulk import processing
- analytics aggregation

Jobs must be idempotent where practical, include useful redacted log context and have bounded retries/backoff.

Queue payloads should generally store identifiers rather than unnecessary PII snapshots or credentials.

Never depend on a shopper request staying open while a slow provider performs non-essential work.

## 22. API strategy

There are three API categories:

### Woo Store API extensions

Use for shopper-facing additional data associated with supported Woo Store API resources such as products/cart/cart items/checkout where extension mechanisms exist.

### Ultimate Commerce REST API

Use `/wp-json/ultimate-commerce/v1/` for UC-owned resources/workflows such as wishlists, returns, saved profiles or provider-neutral tracking actions where appropriate.

### Authenticated Woo/WordPress APIs

Use existing authenticated platform APIs for administrative commerce operations when they already model the needed resource.

Every UC route requires an explicit schema, validation/sanitisation and permission/ownership callback.

API responses should return data, not theme-specific HTML, unless an endpoint is explicitly documented as a fragment-rendering endpoint.

Public/guest APIs must use bounded inputs, pagination/rate controls where appropriate and abuse-resistant tokens.

## 23. Event model and analytics

Ultimate Commerce should emit a stable internal event vocabulary independent of analytics vendor.

Examples:

- `product_viewed`
- `variant_selected`
- `quick_add_opened`
- `product_added_to_cart`
- `cart_updated`
- `checkout_started`
- `purchase_completed`
- `wishlist_item_added`
- `stock_alert_created`
- `return_started`
- `return_submitted`
- `exchange_requested`
- `search_performed`
- `filter_applied`

Events are contracts, not an excuse to leak personal data.

Analytics-provider adapters consume these events subject to the host store's consent/privacy configuration.

Free must not silently send these events to Bad Otter. Pro analytics/value attribution remains merchant-owned by default.

## 24. Admin experience

Ultimate Commerce should live primarily within WooCommerce's administration context rather than creating an unrelated parallel admin universe.

Expected administrative areas across Free/Pro include:

- Dashboard/health
- Modules
- Storefront behaviour
- Product/variation behaviour
- Cart/checkout
- Accounts
- Returns/exchanges
- Tracking
- Merchandising
- Integrations
- Performance/diagnostics
- Developer/system information

The system-status view should expose versions, enabled modules, database schema version, queue health, HPOS compatibility/state and integration health without exposing secrets.

Free-to-Pro discovery may exist but must be restrained and value-led rather than intrusive advertising.

## 25. Configuration

Configuration must be exportable/importable in a safe, versioned form.

Configuration values and operational content must be distinguished.

Examples of configuration:

- low-stock threshold
- enabled product-card components
- module enablement
- returns workflow options

Examples of content/operational data:

- a merchandising campaign
- a customer return
- a wishlist
- tracking events

Secrets must never appear in configuration exports.

## 26. Database and migrations

Custom tables are justified for UC-owned, high-volume or relational workflows where WordPress options/meta would be a poor model.

Potential table families include:

```text
wp_uc_returns
wp_uc_return_items
wp_uc_return_events
wp_uc_wishlists
wp_uc_wishlist_items
wp_uc_stock_alerts
wp_uc_customer_profiles
wp_uc_tracking_events
wp_uc_merchandising_rules
```

Final schemas require design review before implementation.

Every schema change requires a versioned, resumable migration path. Migrations must not rely on manual production SQL as the normal deployment process.

Deactivation does not destroy business/customer data. Destructive uninstall requires an explicit merchant-controlled policy.

## 27. Security and privacy

Security is a platform contract, not a checklist at release time.

Core rule:

> Every request is untrusted. Every sensitive identifier requires authorisation. Every external system can fail or be hostile.

Required practices include:

- use WordPress/Woo authentication rather than inventing session/password systems
- validate and sanitise all external input
- escape output for its rendering context
- use dedicated least-privilege UC capabilities for administrative actions
- use object ownership checks for customer actions/resources
- use nonces/tokens as CSRF protection, not as authorisation
- give every REST route a strict schema and explicit permission callback
- prevent mass-assignment of privileged properties
- use prepared/database APIs for queries
- avoid exposing secrets in logs, diagnostics, exports, REST or frontend code
- minimise stored personal data and classify it as public/operational/personal/secret
- maintain audit trails for high-impact operational actions
- avoid insecure direct object references in order/return/account endpoints
- sign/replay-protect provider webhooks
- make outbound configurable URLs SSRF-safe and keep TLS verification enabled
- use high-entropy expiring tokens for guest workflows
- enforce pagination/query/request size bounds
- use bounded retries and idempotent background work
- never handle raw card numbers/CVV
- integrate UC-owned personal data with WordPress exporter/eraser mechanisms where appropriate

Expected capability families include concepts such as:

- `ulticofo_manage_settings`
- `ulticofo_manage_merchandising`
- `ulticofo_manage_returns`
- `ulticofo_manage_integrations`
- `ulticofo_view_analytics`
- `ulticofo_manage_promotions`

High-impact actions such as manual refund initiation, return override, exchange override, credential changes or operational status manipulation should record actor, timestamp and reason/context where appropriate.

`BLUEPRINT.md` contains the complete product security contract and supply-chain requirements.

## 28. Accessibility

Shopper-facing Ultimate Commerce components target WCAG 2.2 AA.

This includes keyboard operation, focus management, form labels/errors, accessible dialogs/drawers, non-colour-only state communication, usable target sizes and accessible variation/swatch semantics.

A store theme may change appearance but must not be forced to replace accessible UC state/behaviour with inaccessible equivalents.

## 29. Performance

Performance is a release criterion.

Rules:

- no N+1 query patterns across product listings
- no unnecessary frontend assets for disabled/unrelated modules
- scripts should be split by feature/page when practical
- expensive derived data should be cached/materialised with explicit invalidation
- remote APIs must not block core browsing when avoidable
- listing filters/search must remain viable with large variable-product catalogues
- database queries require indexes appropriate to their access patterns
- admin bulk jobs belong in queues/batches
- public collections/endpoints require pagination/bounds

Performance testing must include stores with thousands of products and tens of thousands of variations, not only toy fixtures, and must grow toward larger enterprise fixtures as the product matures.

## 30. Compatibility policy

Ultimate Commerce is built for modern WordPress/WooCommerce.

Minimum supported versions of PHP, WordPress and WooCommerce are recorded in plugin metadata and CI. The minimum must be recent enough to support the architecture without carrying unnecessary legacy paths.

CI should test at least:

- supported minimum versions
- current stable WordPress/WooCommerce
- HPOS enabled
- Cart/Checkout Blocks
- relevant legacy/classic path only where UC explicitly claims support

Compatibility declarations must reflect testing, not wishful metadata.

Ultimate Commerce must avoid undocumented Woo internal namespaces/classes when a supported public API exists.

Additional claims for caching, multilingual, multi-currency, themes/page-builders require explicit compatibility evidence.

## 31. Release engineering and distribution

Ultimate Commerce is treated as a product from the first executable release.

Common requirements:

- semantic versioning
- changelog
- reproducible release ZIP/build
- automated lint/static checks
- unit tests
- integration tests
- end-to-end tests for critical purchase/aftercare flows
- versioned database migrations
- backward-compatible public contracts within a major version where practical
- upgrade testing against realistic data
- dependency/security/package scanning

Breaking public-contract changes require a major-version decision or an explicit deprecation path.

### Free distribution

Ultimate Commerce for WooCommerce is intended for WordPress.org distribution.

Once public, Free updates are delivered through WordPress.org. The WordPress.org package must not contain the Bad Otter custom updater, premium implementation hidden behind licences, unsolicited telemetry or remote executable code.

Free release CI must include Plugin Check, WordPress Coding Standards, security/package inspection and the compatibility/security gates defined in `BLUEPRINT.md` and `BUILD-WORKFLOW.md`.

### Pro distribution

Ultimate Commerce Pro uses Bad Otter managed releases/updates with short-lived GitHub OIDC publication, exact package verification and native WordPress Plugins update delivery.

A Pro release is incomplete until the managed-update path and package integrity are verified.

### Data and entitlement

Licence expiry must never delete or hide merchant/customer data. Local installed functionality must fail safely; hosted services/updates/support may depend on active entitlement as defined in `BLUEPRINT.md`.

## 32. Observability

Ultimate Commerce needs structured diagnostics without drowning stores in logs.

Log entries should include a stable source/context and relevant entity/job/provider identifiers.

Expected observability:

- integration failures
- queue failures/retries
- migration status
- return/tracking workflow history
- webhook/provider verification failures
- unexpected API errors
- security-relevant validation/auth failures where logging is safe and useful

Logs must redact credentials and unnecessary personal information.

## 33. Testing philosophy

Business rules require tests at their owning domain boundary.

Critical end-to-end journeys include:

1. product -> variation -> add to cart
2. cart -> checkout -> Woo order
3. customer -> order -> tracking
4. customer -> return request
5. return -> exchange/refund handoff
6. wishlist and stock-alert ownership
7. module disabled/enabled transitions
8. HPOS order compatibility
9. customer object-level authorisation
10. guest-token abuse/replay boundaries where guest flows exist

Provider contracts should have reusable contract tests so new adapters prove the same behavioural expectations.

Free additionally requires WordPress.org compliance/security regression tests. Pro additionally requires entitlement/update-path tests.

## 34. First vertical slice

Do not build every module before proving the architecture.

The first complete Free slice should demonstrate:

```text
real Woo product
 -> product card contract
 -> product detail/variation state
 -> quick add
 -> basic cart drawer
 -> checkout handoff
 -> real Woo order
 -> account/order contract
```

The first slice may be visually simple. Its job is to prove the boundary between WooCommerce, Ultimate Commerce and the consuming store before broad feature construction.

The first paid slice should then prove that Pro can register advanced variant/cart behaviour through the same public Free contracts without privileged shortcuts.

## 35. Roadmap authority

The detailed implementation sequence now lives in `ROADMAP.md`.

Current priority order is:

1. product split/distribution reset
2. security and WordPress.org engineering baseline
3. Free catalogue/variants/cart vertical slice
4. Free retention/admin completeness
5. WordPress.org public launch
6. first sellable Pro conversion pack
7. merchandising/recommendations
8. customer portal/My Size/stock intelligence
9. returns/exchanges/tracking
10. promotions/automation/value analytics
11. agency/enterprise hardening
12. optional hosted services

Do not revive the old linear `UC 0.1 -> 1.0` sequence if it conflicts with this roadmap.

## 36. Architecture change process

This document and `BLUEPRINT.md` are intentionally strong.

A future implementation may discover a better approach. Changes are allowed, but architectural changes must be deliberate.

A change that affects repository ownership, product-tier ownership, WooCommerce data ownership, dependency direction, public contracts, storage architecture, security model, WordPress.org distribution or module boundaries should be documented in an Architecture Decision Record and reflected back into the source-of-truth documents when accepted.

Convenience in a single task is not sufficient reason to violate the platform boundary.

## 37. Definition of success

Ultimate Commerce succeeds technically when a merchant can install the Free product on a modern WooCommerce store, enable/configure reusable capabilities and deliver a substantially better retail experience without the plugin knowing the merchant's brand or requiring a fork.

It succeeds commercially when Free earns adoption on its own merits, Pro earns renewal through measurable merchant value, agencies can standardise on it, and larger merchants can trust its security, performance, upgrades and data ownership.

That is the standard every module must preserve.
