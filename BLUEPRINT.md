# Ultimate Commerce Master Blueprint

Status: **Master product, commercial, security and platform blueprint**

This document is the product-level source of truth for the Ultimate Commerce family. It defines what Ultimate Commerce is, how Free and Pro are packaged, what Ultimate Commerce Hub is, which shared engines the platform must establish, how the product is secured and distributed, and the long-term product destination.

`FOUNDATION.md` remains authoritative for low-level WooCommerce data ownership, reusable domain boundaries and core engineering rules. `ROADMAP.md` turns this blueprint into implementation order. `BUILD-WORKFLOW.md` defines how changes are designed, tested and released. Accepted ADRs record architecture decisions. If these documents appear to disagree, implementation must stop long enough to reconcile them deliberately rather than silently choosing the convenient interpretation.

The first serious implementation is FishingClothing.co.uk. It is a proving ground for Ultimate Commerce, not a privileged code path.

---

## 1. Product vision

Ultimate Commerce exists to make WooCommerce feel like a much more capable modern retail platform without replacing WooCommerce as the commerce system of record.

The positioning is:

> **Ultimate Commerce is the commerce experience and retail operations layer for WooCommerce.**

WooCommerce remains responsible for core transaction truth. Ultimate Commerce improves how merchants sell, merchandise, personalise, serve customers, operate stock/catalogue workflows, understand performance and automate repetitive work.

Ultimate Commerce should be credible for:

- independent WooCommerce stores
- specialist ecommerce retailers
- omnichannel retailers with stores/warehouses
- agencies standardising a commerce stack across clients
- high-volume merchants that need stronger security, workflows and observability

The product must remain reusable across verticals. Apparel is an important proving use case, but no fashion, fishing, supplier, courier, taxonomy, brand or store-specific behaviour may be hard-coded into the platform.

---

## 2. Product family and naming

| Purpose | Product identity |
| --- | --- |
| Brand | **Ultimate Commerce** |
| WordPress.org Free plugin | **Ultimate Commerce for WooCommerce** |
| Planned WordPress.org slug | `ultimate-commerce-for-woocommerce` |
| Paid companion | **Ultimate Commerce Pro** |
| Paid operations interface | **Ultimate Commerce Hub** — included in Pro, not a separate purchase |
| Future commercial tier | **Ultimate Commerce Business / Enterprise** — plan/tier, not a fragmented add-on marketplace |
| Vendor | **Bad Otter Labs** |
| Canonical PHP namespace | `BadOtter\UltimateCommerce` |
| Canonical storage prefix | `uc_` |
| Canonical REST namespace | `ultimate-commerce/v1` |
| Internal Bad Otter product identity | `ultimate-commerce` |
| Marketing shorthand | **UC** |

Use the official spelling **WooCommerce** everywhere.

The planned WordPress.org slug is not treated as reserved until WordPress.org accepts it.

---

## 3. Commercial packaging

The customer-facing offer should stay simple.

### 3.1 Ultimate Commerce for WooCommerce — Free

Free is a genuinely useful standalone plugin distributed through WordPress.org. Its job is to:

- make WooCommerce noticeably better without payment
- create trust and adoption
- provide stable public contracts used by Pro and third parties
- establish a WordPress.org acquisition channel
- demonstrate quality rather than frustrate users into upgrading

Free is not a crippled Pro build and must not contain paid implementations hidden behind licence checks.

### 3.2 Ultimate Commerce Pro — paid

Pro is one premium product containing the serious revenue, merchandising, customer-lifecycle and operational capabilities.

The merchant should not have to buy separate add-ons for bundles, returns, gift cards, inventory, Hub, recommendations and similar first-party features.

The initial commercial promise is:

> **Sell more. Merchandise better. Serve customers better. Run the store better.**

### 3.3 Ultimate Commerce Hub — included in Pro

Hub is a first-class subsystem of Ultimate Commerce Pro, not a second licence at launch.

A merchant who buys Pro gets the Hub capabilities included according to the modules available in that Pro release.

The standard self-hosted Hub should not initially be priced per staff login. Seasonal or warehouse staff should not create a surprise per-seat bill simply because the software is running on the merchant's own infrastructure.

### 3.4 Future Business / Enterprise tier

A higher commercial tier may later cover materially different scale, governance or support requirements, for example:

- SSO/SAML and enterprise identity integration
- advanced staff provisioning
- large multi-location estates
- advanced approval/governance policies
- long audit retention
- enterprise deployment tooling
- priority support/SLA
- advanced integrations
- very large data/queue workloads

Core Pro value should not be fragmented into dozens of separately purchased micro-addons.

### 3.5 Hosted services

Features with real ongoing Bad Otter infrastructure cost are commercially separate from ordinary local Pro functionality.

Potential hosted services include:

- hosted search/indexing
- AI/ML recommendation inference
- large-scale behavioural modelling
- SMS delivery
- managed email delivery
- hosted image/data enrichment
- large-scale analytics or feed processing

Hosted services may have usage allowances or metered pricing. A merchant must be able to understand what runs locally versus what depends on Bad Otter infrastructure.

---

## 4. Initial pricing principles

Pricing remains subject to market validation.

| Licence | Initial annual hypothesis |
| --- | ---: |
| Single production site | £149–£199 |
| 5 sites | £299–£349 |
| 25 sites / Agency | £599–£699 |

As the mature Pro product includes merchandising, customer portal, returns, tracking, Hub, inventory operations, gift cards, stored value and automation, a single-site price around £249/year may be justified.

Rules:

- no lifetime licence at launch
- recognised local/staging/development environments should not consume paid production-site limits
- licence expiry must never delete merchant/customer data
- licence expiry must not intentionally break the storefront
- updates/support and entitled hosted services may stop when entitlement expires
- historical operational data remains readable/exportable subject to normal permissions
- no dark-pattern admin nags
- no per-staff-seat pricing for standard self-hosted Hub at launch

---

## 5. Repository and dependency topology

Target dependency direction:

```text
WordPress
  -> WooCommerce
      -> Ultimate Commerce for WooCommerce (Free)
          -> Ultimate Commerce Pro (optional)
              -> Ultimate Commerce Hub UI (included Pro subsystem)
              -> Store implementation / theme / site plugin
```

Target repositories:

- `bad-otter-labs/ultimate-commerce` — Free product and public contracts
- `bad-otter-labs/ultimate-commerce-pro` — private paid companion
- merchant repositories — presentation, merchant-specific configuration and store-specific integrations

Hard rules:

1. Free never depends on Pro.
2. Pro consumes documented public Free contracts.
3. Hub consumes UC/Woo public service contracts; it does not reach around them with direct database shortcuts.
4. Pro should not get secret privileged APIs that legitimate third-party extensions cannot use where a public contract is appropriate.
5. Store repositories never copy/fork UC implementation code.
6. FishingClothing-specific copy, styling, policy values and supplier mappings remain outside both UC products.

---

## 6. Core platform principles

1. **WooCommerce owns commerce truth.**
2. **Ultimate Commerce enhances commerce; it does not replace WooCommerce.**
3. **Stores configure; they do not fork.**
4. **Free is useful forever; Pro wins on merchant ROI.**
5. **Pro is one commercial product, internally modular.**
6. **Hub is part of Pro.**
7. **Every request is untrusted.**
8. **Every sensitive object requires authorisation, not just authentication.**
9. **External services are accessed through adapters/contracts.**
10. **Disabled modules do not load unnecessary work/assets.**
11. **Performance, accessibility, security, observability and upgradeability are product features.**
12. **Modules become more valuable together while remaining independently useful.**
13. **Shared concepts are implemented once as platform engines rather than re-created inside each feature.**
14. **Merchant data remains portable and merchant-owned.**
15. **High-impact operations are auditable.**
16. **Background work is resumable/idempotent where practical.**
17. **WooCommerce remains authoritative for totals, tax, payments and refunds.**

---

## 7. Ownership model

### WooCommerce owns

- products and variations
- product publication state
- product prices
- sellable stock quantity/status exposed to commerce flows
- cart state and totals
- coupons where native Woo coupons are used
- taxes
- customers at the WordPress/Woo identity layer
- orders and line items
- payments and gateway state
- monetary refunds
- shipping methods/rates supplied by Woo/provider integrations

Ultimate Commerce uses WooCommerce CRUD/public APIs for these concepts.

### Ultimate Commerce Free may own

- module/settings configuration
- wishlist/recent-view state where implemented locally
- presentation/view-model metadata
- safe caches/derived data
- analytics event contracts and optional local operational data
- migration/schema state

### Ultimate Commerce Pro may own

- product relationship graph
- merchandising/campaign rules
- automation rules and execution history
- customer commerce-profile preferences
- stock-alert subscriptions
- return/exchange workflow records
- provider-neutral tracking events
- Hub locations/operational allocations
- inventory movement ledger
- stock transfers/stocktakes/receipts
- supplier/purchase-order operational records
- staff tasks and approvals
- stored-value accounts and ledger
- gift-card metadata
- operational audit history
- recommendation/materialisation data
- merchant-owned analytics/attribution data

Where UC records refer to Woo objects, UC stores their Woo identifiers instead of cloning authoritative Woo objects.

### Critical inventory rule

WooCommerce remains authoritative for the sellable stock state consumed by checkout and orders.

Ultimate Commerce may own the operational breakdown around that stock: locations, allocations, movements, transfers, counts and reasons. Hub-originated mutations must reconcile operational records to WooCommerce stock through supported APIs. UC must detect/report reconciliation drift rather than silently maintain a conflicting parallel stock truth.

### Critical money rule

Ultimate Commerce may determine eligibility, workflow and presentation for promotions, bundles, gift cards and credits, but WooCommerce remains authoritative for order totals, tax, payments and monetary refund execution.

Any future UC discount calculation engine that materially participates in order total calculation requires a dedicated ADR covering stacking, tax, refunds, calculation priority and compatibility.

---

## 8. Ultimate Commerce Free scope

Free must be an excellent WooCommerce enhancement plugin by itself.

### 8.1 Platform foundation

- dependency/compatibility checks
- central module registry
- public extension contracts
- HPOS compatibility
- Cart/Checkout Blocks compatibility where relevant
- diagnostics/system status
- accessible UI primitives
- versioned configuration import/export excluding secrets
- stable internal event vocabulary
- safe migration framework

### 8.2 Product and catalogue experience

- reusable product/card view models
- configurable product-card components
- basic text/button selectors
- colour/image swatches
- variation selected/unavailable states
- variation-aware media where supported
- basic low-stock presentation
- manual product badges
- basic variation-aware quick add

### 8.3 Cart and retention basics

- Store API/AJAX cart interactions
- basic accessible cart drawer
- quantity/remove controls
- basic wishlist
- recently viewed
- basic account/order view-model hooks

### 8.4 Free product rules

Free should not require:

- a Bad Otter account
- a Pro licence
- a remote service for ordinary local functionality
- a proprietary theme

Free may show restrained, contextual Pro discovery, but must not turn WordPress admin into an advert.

---

## 9. Pro product architecture

Ultimate Commerce Pro is internally organised around seven product pillars:

1. **Convert** — bundles, upsells, cart intelligence and purchase optimisation
2. **Merchandise** — campaigns, product relationships, badges, collections and search merchandising
3. **Personalise** — My Size, profiles and customer-aware recommendations
4. **Serve** — account portal, tracking, returns, exchanges, gift cards/store credit
5. **Operate** — Ultimate Commerce Hub, catalogue operations, inventory and purchasing
6. **Understand** — analytics, stock intelligence, return intelligence and attribution
7. **Automate** — event/rule/action automation across all other pillars

Internally each capability remains independently modular with declared dependencies, settings, migrations, permissions and assets.

---

# PART A — PRO: CONVERT

## 10. Advanced variants and product discovery

Pro extends the Free variant foundation with:

- linked/sibling colour products
- colour-family grouping
- colour-specific URLs where appropriate
- size/option availability on product cards
- preferred-size persistence
- `Available in my size` filters/contracts
- variation image galleries
- advanced quick-add
- stock-aware linked-product strategies
- intelligent unavailable-combination handling
- variant-aware recommendations
- precise variation back-in-stock subscriptions

No vertical is hard-coded. Apparel presets may exist, but the underlying attribute/relationship engine remains generic.

---

## 11. Configurable bundles

Pro should support multiple bundle patterns through one bundle model:

- fixed bundles
- optional-component bundles
- required-component bundles
- mix-and-match bundles
- build-your-own sets
- outfit/Complete the Look bundles
- quantity bundles
- multi-product kits
- promotional bundle savings

Requirements:

- component stock remains Woo-owned
- bundle availability reflects component availability
- variable products can select appropriate component variations
- bundle presentation clearly explains inclusions and savings
- calculation ownership is explicit; UC must not silently create competing totals
- bundle rules are reusable in cards, PDP, cart and campaigns

---

## 12. Upsells, cross-sells and Frequently Bought Together

UC Pro should provide a richer relationship/placement system than Woo's basic product fields.

Supported strategies include:

- manual upsells
- manual cross-sells
- frequently bought together derived from merchant order data
- complementary category/attribute rules
- Complete the Look
- accessories
- upgrades
- lower-price alternatives
- premium alternatives
- replacement products
- post-purchase buy-again suggestions

Placements may include:

- PDP
- cart drawer
- cart page
- account portal
- order confirmation
- search/collection zero-result recovery

Merchants control placement, strategy priority and fallback rules.

---

## 13. Smart out-of-stock recovery

An out-of-stock product should not be a dead end.

UC Pro should be able to suggest, in configurable order:

- same style in another colour
- same product in another acceptable size/option
- linked sibling product
- manually defined substitute
- same brand/category substitute
- nearest price/specification substitute
- back-in-stock notification
- expected-restock message where reliable data exists

Example customer experience:

```text
Black / Large is unavailable.
Available now in Olive / Large.
Or view similar alternatives / notify me when Black / Large returns.
```

Recommendation logic must not pretend an alternative is equivalent when it is not. Relationship type and reasoning should be inspectable.

---

## 14. Advanced cart and purchase conversion

Pro cart capabilities may include:

- cart recommendations
- Complete the Look
- Frequently Bought Together
- free-delivery progress
- free-gift progress
- spend-threshold incentives
- contextual cross-sells
- saved cart
- save-for-later
- variation editing
- gift options
- delivery/promotion messages
- stock-aware suggestions

UC should help the merchant increase basket value without turning checkout/cart into a cluttered or deceptive experience.

---

## 15. Additional conversion components

Potential Pro components include:

- sticky add-to-cart
- advanced quick view
- product comparison
- reorder / Buy Again
- gift message and gift wrap configuration
- post-purchase recommendations that do not alter an already charged order without a new explicit transaction
- delivery eligibility/promise presentation
- contextual trust/benefit messaging

Accessibility and performance requirements apply to all components.

---

# PART B — PRO: MERCHANDISE

## 16. Product Relationship Graph

Ultimate Commerce should establish one reusable graph for product relationships rather than separate tables for every feature.

Relationship types may include:

- `alternative`
- `accessory`
- `colour_sibling`
- `bundle_component`
- `complete_the_look`
- `upgrade`
- `downgrade`
- `replacement`
- `compatible_with`
- `frequently_bought_with`

Relationships may be:

- manually curated
- rule-generated
- derived from order behaviour
- supplied by an external provider

Every relationship should carry enough metadata to explain its source/type and permit deterministic overrides.

This graph powers bundles, OOS recovery, recommendations, accessories, Complete the Look and related merchandising without duplicating the same concept.

---

## 17. Dynamic collections and merchandising engine

Merchants should be able to create rule-driven collections using inputs such as:

- taxonomy/attribute membership
- price range
- stock state
- location availability where relevant
- publication/newness age
- sale state
- product relationship
- sales velocity
- conversion data
- return/fit signals where appropriate
- margin/cost signals when merchant-owned cost data is available

Features:

- include/exclude
- pin
- boost/bury
- deterministic ordering
- scheduled activation
- manual override
- rule explanation/debug view
- stock-aware ranking

Example:

```text
Waterproof jackets
AND in stock
AND Men's XL available
AND £100–£250
EXCLUDE sale
SORT by stock depth then conversion signal
```

The system should explain why a product is present or absent.

---

## 18. Campaign Engine

One campaign definition should be able to coordinate multiple customer-facing experiences.

A campaign may control:

- announcement/notice bars
- category/collection banners
- product badges
- product-image overlays
- sale/offer labels
- cart messaging
- free-gift messaging
- launch countdowns
- campaign-specific recommendations
- scheduled collection activation
- search merchandising boosts

A Christmas campaign, for example, can activate multiple surfaces at a configured time and disable them together later.

Campaigns require:

- start/end schedule
- timezone-aware execution
- audience/placement rules
- preview state
- draft/published state
- audit history
- optional approval workflow

---

## 19. Product badges, sale tags and image overlays

Pro should replace the simplistic one-size-fits-all sale badge with a configurable badge/overlay system.

Examples:

- Sale
- `-30%`
- New
- Bestseller
- Limited
- Exclusive
- Staff Pick
- Low Stock
- Back in Stock
- Web Exclusive
- Last Chance

Rules may be based on:

- discount percentage/value
- sale state
- launch date
- stock
- category/brand
- campaign
- sales velocity
- merchant-defined conditions

Overlay presentation remains themeable. UC supplies semantic state and reusable rendering primitives rather than hard-coding brand styling.

---

## 20. Fully configurable notice / announcement system

The notice system should support multiple messages and placements rather than a single global text field.

Capabilities:

- rotating messages
- icons/links
- dismissibility
- schedules
- campaign association
- device targeting
- page/product/category targeting
- customer/login targeting
- cart-value/eligibility targeting
- location/market targeting where legally and technically appropriate
- countdown/delivery cut-off presentation based on real configured data

Examples:

```text
FREE UK DELIVERY OVER £75
ORDER BEFORE 3PM FOR SAME-DAY DISPATCH
DIGITAL GIFT CARDS — DELIVERED INSTANTLY
```

No fake urgency or fabricated scarcity.

---

## 21. Promotion presentation and incentive orchestration

UC Pro may orchestrate/present:

- spend thresholds
- category/brand campaigns
- tiered incentive messaging
- quantity-break messaging
- free gifts
- BOGO presentation
- coupon discoverability
- automatic-offer messaging
- bundle savings
- gift-card promotions

Where WooCommerce or another compatible pricing engine owns monetary discount calculation, UC displays and explains the authoritative result rather than independently recalculating a conflicting total.

Promotion conflict UI should be able to explain why an offer applies or does not apply.

---

## 22. Product launches and scheduled merchandising

Pro should support reusable launch workflows such as:

- Coming Soon state
- notify-at-launch subscriptions
- launch countdown
- launch collection
- scheduled badge/banner
- scheduled product publication through supported Woo/WordPress mechanisms

A proper preorder engine, if added later, requires a separate architecture decision because payment capture, stock and fulfilment semantics are materially different.

---

## 23. Search and navigation merchandising

UC should expose provider-neutral search merchandising concepts including:

- synonyms
- pinned results
- promoted/buried results
- zero-result recommendations
- typo-tolerance provider integration
- facet/filter contracts
- stock/size availability filters
- campaign boosts
- merchandising rules

Navigation support may include reusable data blocks for:

- featured categories
- brands
- attributes
- promotional menu tiles
- trending searches
- recently searched terms

Theme/store code owns final presentation.

---

# PART C — PRO: PERSONALISE

## 24. My Size / Commerce Profile

The customer commerce profile should support merchant-defined personal shopping preferences such as:

- top size
- trouser waist/leg
- footwear size
- fit preference
- preferred brands/attributes where appropriate

Apparel presets are configuration templates, not hard-coded runtime branches.

Features:

- `Available in my size`
- Shop My Size
- preferred-size persistence
- size-aware recommendations
- saved fit preference
- brand/product fit intelligence over time
- privacy/consent controls

Any future statistical fit confidence must clearly distinguish model-derived guidance from guaranteed fit.

---

## 25. Recommendations

Recommendations begin deterministic/rules-first and may later accept behavioural/ML providers behind the same contract.

Strategies include:

- curated
- related collection
- same brand
- complementary taxonomy
- product relationship graph
- Complete the Look
- FBT from order data
- recently viewed
- trending
- customer-size-aware
- stock-aware
- campaign-aware

Every placement should support fallback chains and appropriate exclusions.

---

## 26. Review / fit intelligence

Where reviews are provided natively or by a provider, UC may expose richer review/fit concepts such as:

- verified purchase state supplied by the authoritative review/order source
- fit feedback
- warmth/quality/attribute ratings
- review filtering
- true-to-size aggregation
- merchant-defined structured review attributes

Return reasons and review feedback may contribute to future fit guidance, but the system must avoid presenting weak data as certainty.

---

# PART D — PRO: SERVE

## 27. Customer Portal

The Pro account experience should feel like a modern retailer rather than default Woo account pages.

Potential sections:

- overview
- order history
- visual order timeline
- shipment tracking
- return/exchange actions
- saved addresses/payment-method links provided by Woo/gateways
- wishlist
- recently viewed
- My Size/preferences
- stock alerts
- gift cards/store credit wallet
- communication preferences
- Buy Again

Server-side ownership checks are mandatory for all customer resources.

---

## 28. Returns and exchanges

Returns are a real workflow, not a boolean order flag.

Return/RMA records may include:

- owning Woo order
- line items/quantities
- requested resolution
- reason codes
- customer detail where required
- eligibility decision/rule version
- timestamps
- method/provider reference
- inspection state
- outcome
- refund/exchange handoff
- actor/audit history

Capabilities:

- self-service return request
- configurable return windows/policy inputs
- partial returns
- exchange for size/variation
- stock reservation policies
- QR/label provider contracts
- return status timeline
- analytics

Monetary refunds are executed through Woo/payment APIs.

---

## 29. Tracking

UC defines a stable provider-neutral shipment model with statuses such as:

- created
- packed
- dispatched
- in_transit
- out_for_delivery
- delivered
- delivery_failed
- returned_to_sender
- returned

Features:

- multiple shipments per order
- courier adapters
- signed webhooks and/or polling
- customer tracking page/timeline
- dispatch/delivery notification events
- provider-failure isolation

No courier-specific logic belongs in generic storefront/account templates.

---

# PART E — PRO: GIFT CARDS, STORE CREDIT AND WALLET

## 30. Stored Value Engine

Gift cards, return credit and goodwill credit should share one secure stored-value foundation rather than independent balance systems.

Potential account types:

- purchased gift card
- return/store credit
- goodwill credit
- promotional credit
- referral credit
- future loyalty/reward credit

The stored-value engine must be ledger-based. A balance is derived from transactions rather than being the only source of history.

Example:

```text
+100.00 Purchase
-35.00 Order #9281
-20.00 Order #9417
+20.00 Reversal
Balance: 65.00
```

Ledger records should be append-only except for narrowly controlled metadata corrections. Financial corrections are recorded as new reversing/adjusting entries.

---

## 31. Gift Cards & Vouchers

Gift Cards is a headline Pro module.

Customer capabilities:

- fixed values
- custom amount within merchant limits
- digital gift cards
- optional physical cards
- recipient name/email
- sender name
- gift message
- themed designs
- scheduled delivery
- multiple recipients where supported
- add card/credit to customer wallet
- secure balance checking

Seasonal use cases include Christmas, birthday, thank-you and congratulations campaigns.

Scheduled delivery should support examples such as purchase now and send on Christmas morning at a configured local time.

---

## 32. Gift-card redemption

Required behaviour:

- partial redemption
- remaining balance retained
- configurable multiple-card use per order
- mixed payment: stored value plus normal gateway payment
- safe refund restoration rules
- idempotent redemption
- concurrency protection against double-spending
- clear order/payment metadata without exposing full card codes

If an £80 order uses £50 stored value and £30 card payment, refund allocation must be deterministic/configurable and use Woo/payment APIs for the external payment portion.

---

## 33. Physical gift cards and Hub management

Hub should support physical gift-card workflows:

- scan card
- check balance
- activate card
- issue/add value subject to policy
- redeem
- replace a damaged/lost card through controlled workflow
- transfer balance under appropriate approval
- view transaction history
- void/disable where permitted

Unactivated physical cards should have no stored value.

QR/barcode support should allow shop staff to operate cards without WordPress admin access.

---

## 34. Corporate and bulk gifting

Future Pro/Business capabilities may include:

- bulk gift-card generation
- CSV recipient upload
- scheduled bulk delivery
- corporate branding
- reporting/export
- invoiced corporate purchase workflow where separately designed

This opens B2B gifting without changing the core stored-value model.

---

## 35. Stored-value security

Gift cards are money-like instruments and require enhanced controls:

- high-entropy non-sequential codes
- masked display
- safe hashing/tokenisation where practical
- rate-limited balance/validation endpoints
- transaction locking/concurrency control
- idempotent redemption
- append-only audit history
- manager approval for large manual credits where configured
- no secret/full code exposure in logs
- suspicious activity events for future risk tooling

Expiry, tax, accounting and escheat/unclaimed-property handling vary by jurisdiction and must be configurable/policy-driven rather than hard-coded as universal legal assumptions.

---

# PART F — PRO: OPERATE / ULTIMATE COMMERCE HUB

## 36. Hub product definition

Ultimate Commerce Hub is the operational staff application included with Pro.

Its purpose is to let staff perform everyday commerce operations without learning or entering WordPress admin.

A merchant should be able to give a warehouse/store employee access to something like:

```text
staff.example.com
```

or an equivalent route/application surface, while WordPress/WooCommerce remain the authenticated backend and commerce engine.

Hub should feel like purpose-built retail software, not a reskinned wp-admin screen.

Design goals:

- mobile-first
- PWA-capable where practical
- fast search
- barcode/scan-first workflows
- large touch targets
- keyboard/scanner friendly
- minimal training requirement
- role-aware home screen
- no unnecessary WordPress concepts

---

## 37. Hub authentication and staff identity

Hub should reuse the secure WordPress identity layer rather than inventing a parallel password database.

Requirements:

- dedicated UC capabilities
- staff can be denied wp-admin while permitted Hub access
- location-scoped permissions
- optional role presets
- configurable session policies
- hooks/integration for MFA/2FA
- future enterprise SSO/SAML support
- login/rate-limit compatibility with established WordPress security infrastructure

Authentication does not imply authorisation. Every Hub action still checks the exact capability and resource/location scope.

---

## 38. Hub role and capability model

Avoid hard-coding permissions to job titles. Define capabilities and ship sensible presets.

Conceptual capabilities include:

- view stock
- adjust stock
- receive stock
- perform stocktake
- transfer stock
- approve large adjustments
- create product
- edit product
- edit price
- view cost price
- publish product
- archive product
- view orders
- pick/pack orders
- view customer data
- process returns
- approve refund handoff
- issue stored value
- manage gift cards
- create purchase orders
- approve purchase orders
- manage staff
- view operational analytics

A role such as `Seasonal Warehouse Staff` can be assembled from capabilities without granting wp-admin access.

Location scope is independent from job title: a staff member may be allowed to manage only one store or warehouse.

---

## 39. Hub home / work dashboard

The Hub home screen should be job-focused.

Potential cards:

- orders awaiting pick
- orders awaiting dispatch
- returns awaiting inspection
- stock transfers awaiting receipt
- low-stock items
- products at risk of stockout
- negative/reconciliation exceptions
- stocktakes due/overdue
- products awaiting catalogue approval
- purchase orders awaiting action
- tasks assigned to the staff member

Each number is actionable, not decorative.

---

## 40. Catalog Operations

Hub must let authorised staff create and maintain WooCommerce products without entering wp-admin.

The UX should be task-based:

```text
Identify -> Describe -> Variants -> Pricing -> Inventory -> Images -> Review -> Publish
```

Supported product operations:

- create simple products
- create variable products
- edit existing products
- draft/submit for approval
- publish when permitted
- archive/discontinue
- duplicate from a similar product
- bulk-create related products
- barcode/SKU lookup
- product image capture/upload
- supplier metadata
- opening inventory by location

The Hub writes products/variations through WooCommerce CRUD/public APIs. It does not create a second catalogue.

---

## 41. Product templates

Merchants can define product templates such as `Jacket`, `Footwear`, `Television` or any other vertical concept.

A template controls:

- required/optional fields
- descriptive attributes
- variant-generating attributes
- validation rules
- default categories/taxonomies where configured
- inventory fields
- supplier fields
- approval requirements

Templates are configuration, not hard-coded product types.

Example apparel template fields might include Colour, Size, Fit, Material and Waterproof Rating; another merchant can define completely different attributes.

---

## 42. Variant matrix creation

Hub should make complex Woo variable products easy for non-WordPress staff.

Example:

```text
Colours: Black, Olive, Navy
Sizes: S, M, L, XL
```

UC generates the candidate combinations and presents a matrix for:

- SKU
- barcode/GTIN
- price override where allowed
- opening stock
- images
- enabled/disabled state

The staff member should not need to understand WooCommerce implementation terminology to create a valid variable product.

---

## 43. Barcode-first product intake

A strong Hub flow is:

```text
Scan barcode
 -> product exists? open product / receive stock
 -> product missing? create product with barcode pre-filled
 -> configure variants/metadata
 -> receive stock
 -> submit/publish according to permission
```

Duplicate detection should check, where available:

- SKU
- GTIN/EAN/UPC/barcode
- supplier SKU
- brand/title similarity as a warning signal

The system should favour preventing duplicate catalogue records over forcing staff to clean them up later.

---

## 44. Product approval workflow

`Can create product` must not automatically mean `can publish product`.

A typical workflow:

```text
Draft -> Awaiting Review -> Approved -> Published
```

Warehouse staff can capture the product and inventory while a merchandising manager reviews customer-facing content before publication.

Ordinary staff should archive products rather than destructively delete products that have order, return or inventory history.

---

## 45. Inventory Operations

Hub inventory functions should include:

- search stock by SKU/barcode/product
- view available/reserved/operational quantities
- receive goods
- adjust stock
- mark damaged stock
- shrinkage/loss adjustments
- customer-return restock/quarantine
- stocktake
- transfer between locations
- reconciliation exceptions
- low-stock alerts
- stock movement history

A change should not merely mutate a number. It should create an inventory movement record with actor/reason/context.

---

## 46. Inventory Movement Ledger

The inventory movement ledger is a foundational Pro engine.

A movement should capture concepts such as:

- product/variation ID
- quantity delta
- source/destination location where relevant
- reason code
- actor/system
- timestamp
- related transfer/receipt/return/order/stocktake reference
- before/after operational balance where useful
- free-text note only where policy permits

Example:

```text
-4 units
Reason: Damaged
Location: Warehouse A
Actor: staff user 184
Reference: ADJ-218
```

Movements should be auditable and not silently deleted.

---

## 47. Multi-location inventory

The data model should understand locations from the beginning even if the first release uses only one location.

Location types may include:

- warehouse
- retail store
- stockroom
- fulfilment site
- quarantine/returns area where appropriate

Future features enabled by this model include:

- store availability
- click & collect
- ship from store
- local pickup stock
- nearest-store availability

UC location balances must reconcile to WooCommerce sellable stock according to a documented strategy. The implementation must not create an unexplained second stock truth.

---

## 48. Stock transfers

Transfer workflow:

```text
Requested -> Approved (optional) -> Picked -> In Transit -> Received -> Closed
```

Requirements:

- transfer line items
- source/destination
- actor history
- partial receipt support
- discrepancy handling
- barcode scanning
- cancellation policy
- reconciliation

A transfer does not imply physical receipt until the receiving location confirms it.

---

## 49. Stocktake and cycle counts

Hub should support:

- full stocktakes
- location/zone counts
- cycle counts
- blind counts where configured
- barcode scanning
- discrepancy review
- manager approval threshold
- resumable counting sessions
- adjustment posting only after completion/approval policy

This must remain usable on phones/tablets and scanner-equipped desktop stations.

---

## 50. Receiving and supplier deliveries

Receiving should support:

- scan existing product
- identify missing products
- create product when authorised
- receive full/partial quantities
- record discrepancies
- assign stock location
- connect receipt to supplier/PO where available
- quarantine damaged/incorrect deliveries

Receiving must be idempotent enough to prevent accidental double receipt from browser retries or duplicate actions.

---

## 51. Suppliers and Purchase Orders

A future purchasing module should support:

- supplier records
- supplier SKU/reference
- cost price where permitted
- lead time
- pack quantity
- purchase orders
- purchase-order lines
- draft/approval/ordered/partial-receipt/received/closed states
- inbound quantity visibility
- delivery discrepancy

Suggested reorder can later use:

- current stock
- inbound stock
- sales velocity
- configured safety stock
- lead time

UC is not trying to become a complete ERP. Purchasing exists to support the commerce/inventory workflows that materially improve WooCommerce operations.

---

## 52. Stock Intelligence

Move beyond `stock < 5`.

Potential insights:

- days of cover
- likely stockout window
- fastest-moving variants
- dead/slow stock
- high-demand/low-stock items
- size/colour imbalance
- out-of-stock frequency
- inbound stock coverage
- replenishment suggestions

Example:

```text
Jacket / Black / Large
8 remaining
3.4 sold per week
Approx. 16 days cover
```

Forecasting language must remain appropriately qualified when data is sparse.

---

## 53. Alerts and Morning Operations Brief

UC should have a reusable Alert/Notification Engine.

Operational alert examples:

- stock below threshold
- predicted stockout
- unusual/large adjustment
- negative stock/reconciliation issue
- transfer overdue
- stocktake overdue
- return awaiting inspection
- product awaiting approval
- purchase order overdue
- failed provider/job

Delivery channels may include:

- Hub notifications
- email
- push where supported
- Slack/Teams provider adapters later
- SMS as a hosted/usage-billed service where appropriate

A daily Morning Operations Brief can summarise the most important actionable exceptions.

---

## 54. Staff tasks

Hub may expose operational tasks such as:

- replenish shelf/location
- recount SKU
- inspect return
- receive transfer
- review product
- resolve inventory discrepancy

Tasks should be assignable, auditable and linked to the relevant UC/Woo object without becoming a general-purpose project-management product.

---

## 55. Orders, picking and dispatch in Hub

Hub can provide operational views for:

- orders awaiting pick
- pick lists
- packing queues
- dispatch status
- collection orders
- shipment creation handoff
- order lookup

WooCommerce remains authoritative for the order. Hub provides a simplified operational workflow around it.

---

## 56. Returns in Hub

Staff should be able to:

- identify return/RMA
- receive returned item
- inspect condition
- restock
- quarantine
- reject/flag according to policy
- initiate approved exchange/refund handoff
- record reason/outcome

Customer-service/warehouse roles should be separable from financial approval roles.

---

## 57. Generic Approval Framework

Multiple modules need approvals. Build the workflow once.

Conceptual lifecycle:

```text
Requested -> Awaiting Approval -> Approved / Rejected -> Executed / Cancelled
```

Uses include:

- large stock adjustment
- product publication
- purchase order
- manual gift-card/store credit
- return/refund override
- campaign publication
- high-risk transfer

Approval records should include requester, approver, reason, timestamps and target/action context.

---

# PART G — PRO: UNDERSTAND

## 58. Advanced analytics and value attribution

Pro should help merchants understand whether UC is creating value.

Examples:

- recommendation-attributed revenue
- Complete-the-Look conversion
- FBT conversion
- bundle revenue
- cart-incentive uplift
- free-delivery threshold contribution
- OOS alternative conversion
- back-in-stock conversion
- campaign performance
- collection performance
- gift-card sales/redemption
- basket value when gift cards are redeemed
- return/exchange rate
- return reasons
- size/fit outcomes
- stockout frequency
- inventory adjustment/shrinkage patterns

Attribution should be documented and not presented as stronger causality than the data supports.

---

## 59. Gift-card / stored-value reporting

Merchant reporting may include:

- outstanding stored-value liability
- gift cards sold
- redeemed value
- remaining balances
- average gift-card purchase
- average order value on redemption
- expired/disabled balances subject to merchant accounting policy
- promotional credit issued/redeemed

Sensitive identifiers remain masked.

---

## 60. Operations analytics

Hub reporting may include:

- stock movement volume
- adjustments by reason
- receiving discrepancies
- transfer performance
- stocktake discrepancies
- dead stock
- stockout risk
- staff activity/audit views
- purchase-order fill rate

Staff analytics should be used for operational accountability, not covert surveillance. Merchants should understand what staff activity is logged.

---

# PART H — PRO: AUTOMATE

## 61. Rules & Automation Engine

Ultimate Commerce should establish one reusable event/rule/action engine.

Conceptual model:

```text
WHEN <event or schedule>
IF <conditions>
THEN <one or more actions>
```

Examples:

```text
WHEN stock cover < 14 days
IF category = Jackets
THEN add Low Stock badge
AND alert purchasing
AND reduce recommendation priority
```

```text
WHEN campaign starts
THEN activate sale overlay
AND publish collection
AND activate notice bar
AND boost selected products in search
```

```text
WHEN return reason = too_small reaches threshold
THEN create merchandising/fit insight
```

Automations must be auditable, idempotent and bounded. A rule must not be able to create uncontrolled recursive execution.

---

## 62. Event vocabulary

The platform should expose stable merchant/provider-neutral events, for example:

- `product_viewed`
- `variant_selected`
- `product_added_to_cart`
- `cart_updated`
- `checkout_started`
- `purchase_completed`
- `wishlist_item_added`
- `stock_alert_created`
- `inventory_adjusted`
- `stock_received`
- `stock_transfer_created`
- `product_created`
- `product_approved`
- `return_started`
- `return_received`
- `exchange_requested`
- `gift_card_purchased`
- `stored_value_redeemed`
- `campaign_started`
- `automation_executed`

Events are contracts, not an excuse to leak personal data.

---

# PART I — SHARED PLATFORM ENGINES

## 63. Shared engines to establish deliberately

The following are platform primitives and should not be reinvented inside individual modules:

| Engine / primitive | Primary use |
| --- | --- |
| Module Registry | lifecycle, dependencies, enable/disable |
| Product Relationship Graph | alternatives, bundles, siblings, accessories, recommendations |
| Availability Service | stock/variant/location-aware availability states |
| Campaign Engine | schedules, badges, overlays, notices, collection/search campaign state |
| Rules & Automation Engine | events + conditions + actions |
| Inventory Movement Ledger | operational stock history |
| Location Model | stores, warehouses and operational allocations |
| Stored Value Ledger | gift cards, store credit, goodwill/promotional credit |
| Approval Framework | high-risk workflow approvals |
| Alert/Notification Engine | operational/customer event delivery |
| Audit Trail | high-impact actor/action history |
| Provider Registry | tracking, search, notifications, returns, analytics, recommendations |
| Secret Store | provider secrets/credentials |
| Background Job Framework | idempotency, retries, locks, progress, dead work |
| Product Template Schema | guided Hub catalogue creation |
| Event/Analytics Vocabulary | cross-module events and attribution |
| Configuration Registry | versioned settings/import/export |

A new feature should use these engines where the concept fits rather than creating a feature-specific imitation.

---

## 64. Availability Service

Availability is richer than a single stock integer.

UC should expose reusable states such as:

- in stock
- low stock
- out of stock
- available in another variation
- available in another linked product/colour
- available at another location
- available for collection
- inbound/restock expected where reliable
- discontinued

The service consumes authoritative Woo stock plus UC operational context. Storefront modules should not each calculate availability differently.

---

## 65. Provider contracts

Expected provider families include:

- `TrackingProvider`
- `ReturnProvider`
- `SearchProvider`
- `NotificationProvider`
- `AnalyticsProvider`
- `RecommendationProvider`
- future supplier/feed providers where justified

Provider adapters translate external vocabulary/errors into UC concepts.

A provider outage must degrade safely. Tracking downtime must not prevent checkout. Email timeout must not duplicate gift cards. Search-provider failure should have an intentional fallback strategy where possible.

---

## 66. Demo / Sandbox mode

Pro should eventually provide a safe demonstration/sandbox mode for merchants and agencies.

Possible sample data/configuration:

- campaigns
- bundles
- recommendations
- stock alerts
- Hub workflows
- gift cards

Demo mode must not mutate real production orders, balances or inventory without explicit transition to live configuration.

---

# PART J — SECURITY MASTER CONTRACT

## 67. Security posture

Ultimate Commerce must be designed as if it may run on large, high-value stores.

Core rule:

> **Every request is untrusted. Every sensitive identifier requires authorisation. Every external system can fail or be hostile.**

Security is a release criterion, not a cleanup phase.

---

## 68. Authentication

- use WordPress/WooCommerce identities instead of inventing a separate password store
- Hub may provide a separate user experience but not a weaker authentication model
- integrate cleanly with MFA/2FA providers
- provide future SSO/SAML hooks for Enterprise
- use secure cookies/session handling supplied by WordPress where applicable
- do not expose authentication tokens in URLs/logs

---

## 69. Authorisation and object ownership

Authentication alone is insufficient.

UC requires:

- least-privilege capabilities
- object-level ownership for customer resources
- location scope for operational users
- explicit permission callbacks for REST routes
- approval policies for high-impact actions

Conceptual capabilities include:

- `uc_manage_settings`
- `uc_manage_merchandising`
- `uc_manage_promotions`
- `uc_manage_returns`
- `uc_manage_integrations`
- `uc_view_analytics`
- `uc_view_inventory`
- `uc_adjust_inventory`
- `uc_receive_inventory`
- `uc_manage_catalogue`
- `uc_publish_products`
- `uc_manage_gift_cards`
- `uc_manage_purchasing`

Do not scatter `manage_options` as a substitute for product-specific permissions.

---

## 70. CSRF, nonces and request validation

Nonces are CSRF protection, not authorisation.

A sensitive mutation generally requires:

1. authentication or a purpose-built guest token
2. CSRF protection where applicable
3. capability/ownership/scope verification
4. strict request schema
5. validation and sanitisation
6. bounded sizes/ranges
7. safe persistence
8. context-correct output escaping

REST schemas must prevent mass assignment of privileged fields such as user ID, internal status, refund value or approval state.

---

## 71. Database and query security

- use Woo CRUD/public APIs for Woo-owned data
- use prepared WordPress database APIs for UC tables
- index high-volume access patterns
- no arbitrary public `limit=-1`
- no direct order assumptions that break HPOS
- no convenience copies of sensitive Woo data without a justified design

---

## 72. Hub operation security

High-impact Hub actions require stronger controls:

- inventory adjustments record actor/reason
- threshold-based approval can be configured
- product price/publication permissions are separate from stock permissions
- destructive product deletion is avoided for ordinary staff
- stored-value manual issue/add-value can require approval
- cost price/customer PII permissions are separable
- every mutation is server-authorised regardless of disabled/hidden UI controls

---

## 73. Gift-card / stored-value security

In addition to the Stored Value requirements above:

- never use sequential/predictable public codes
- full codes are never printed in ordinary logs
- balance endpoints are rate-limited/abuse-resistant
- redemption uses transactional/concurrency-safe logic
- retries cannot double-redeem
- reversals are ledger entries
- suspicious repeated validation attempts should become security events

---

## 74. Webhooks

A shared webhook gateway should support:

- signature/HMAC verification
- timestamp tolerance
- constant-time comparison
- replay protection
- event allowlists
- body-size limits
- idempotency/deduplication
- safe retry semantics

IP allowlisting alone is never sufficient authentication.

---

## 75. Outbound HTTP and SSRF

- use WordPress HTTP APIs
- keep TLS verification enabled
- provider adapters prefer known endpoints
- user-configured URLs require validation and SSRF-safe handling
- do not provide untrusted users a generic internal/private-network fetch capability

---

## 76. Secrets

Secrets use a narrow secret-store abstraction.

Secrets must never appear in:

- frontend JavaScript
- REST responses
- config export
- ordinary logs
- diagnostics export
- source control

Higher-security deployments should support encryption material supplied through environment/`wp-config.php` where practical.

---

## 77. File/media handling

Product/media uploads and import files require:

- capability checks
- size limits
- MIME/type validation
- safe WordPress media APIs
- no executable upload path created by UC
- safe parsing of CSV/import formats
- bounded image/file processing

---

## 78. Guest flows

Guest returns, tracking or balance checks must not rely on predictable identifiers alone.

Use:

- high-entropy purpose-bound tokens
- expiry
- safe token storage
- rate limiting
- revocation/one-time semantics where appropriate

---

## 79. Payments and PCI boundary

Ultimate Commerce never handles raw card number/CVV/card authentication data.

Payment processing remains in WooCommerce gateways. UC consumes safe order/payment state only.

---

## 80. Abuse and denial-of-service resistance

- pagination and max page sizes
- bounded date/query ranges
- rate limits for expensive guest/public operations
- no user-controlled unbounded queue fan-out
- bounded provider retries/backoff
- expensive derived data cached/materialised deliberately
- bulk Hub operations resumable/batched

---

## 81. Background jobs and concurrency

Action Scheduler/Woo-supported scheduling is the local default unless a future ADR changes it.

Jobs should:

- carry IDs rather than unnecessary PII snapshots
- be idempotent where practical
- have bounded retries
- use locks/deduplication where concurrent execution is unsafe
- expose progress for long-running batches
- surface dead/failed work in diagnostics

Critical workflows such as stock receipt, gift-card redemption and transfer receipt must protect against duplicate execution.

---

## 82. Privacy and data minimisation

Classify data as:

- public
- merchant operational
- personal
- credential/secret

UC-owned personal data should integrate with WordPress privacy exporter/eraser mechanisms where appropriate.

Retention must be defined for high-volume logs/events. Do not keep unnecessary personal data forever because storage is cheap.

---

## 83. Logging and audit

Logs must redact credentials and unnecessary personal data.

High-impact actions should record actor, timestamp, target and reason/context where appropriate.

Audit events include:

- stock adjustments
- product publication/price changes
- return/exchange overrides
- refund handoff
- manual stored-value issue/adjustment
- purchase-order approval
- campaign publication
- integration credential change
- permission/role changes

Audit records are not normal application debug logs and should have appropriate retention/export controls.

---

## 84. Supply-chain and release security

- lock dependencies
- Composer/npm vulnerability scanning
- secret scanning
- production-only package inspection
- reproducible builds where practical
- provenance/checksums
- SBOM before enterprise launch where useful
- automated static/security checks
- independent security review before major-enterprise positioning
- documented private vulnerability reporting route / `SECURITY.md`

---

# PART K — WORDPRESS.ORG CONTRACT FOR FREE

## 85. WordPress.org-first engineering

Ultimate Commerce Free is designed for directory compliance from the beginning, not rewritten for compliance at submission time.

Hard requirements for the public Free package:

- independently useful without Pro
- WordPress.org public updates
- no Bad Otter custom updater/update override
- no automatic third-party download/install of Pro
- no paid implementation hidden in Free behind licence/payment checks
- no remote executable code
- no unnecessary remotely hosted static assets for ordinary local features
- no unsolicited tracking or activation ping
- external-service use clearly disclosed
- telemetry beyond technically essential operation is transparent/consent-aware
- public/reviewable human-readable source for compiled assets
- reproducible build instructions
- translation-ready strings
- WordPress/Woo public APIs
- WordPress HTTP API
- standard sanitisation/validation/escaping
- restrained upgrade UI

Free must not behave like an installer, trialware shell or advertising framework.

---

## 86. WordPress.org release gate

Before a Free release/submission:

- Plugin Check has no blocking repository issues
- PHPCS/WPCS passes
- PHP compatibility/static analysis passes
- dependency vulnerability scans pass
- secret scan passes
- package inspection passes
- permission/ownership tests pass
- REST/CSRF tests pass
- HPOS matrix passes
- relevant Cart/Checkout Blocks tests pass
- plugin/readme/stable-tag/version metadata aligns
- human-readable source/build docs are present
- no development junk/credentials/nested release packages ship
- upgrade from realistic existing data is tested

Passing automation does not replace manual security/repository review.

---

# PART L — PERFORMANCE, SCALE AND RELIABILITY

## 87. Performance principles

- no N+1 product-grid queries
- disabled modules load no unrelated frontend work
- assets split by feature/page where practical
- indexes match real access patterns
- pagination everywhere high-volume data appears
- no full-table scans on ordinary shopper requests
- expensive computation cached/materialised with explicit invalidation
- remote providers do not block core shopping flows where avoidable
- admin/Hub bulk work goes through resumable batches

---

## 88. Scale targets

The architecture must be tested beyond toy fixtures.

Benchmark tiers should include representative datasets such as:

- thousands to tens of thousands of products
- very large variable-product catalogues
- hundreds of thousands of variations
- large order/customer histories
- high-volume inventory movement tables
- concurrent Hub staff operations

Later enterprise benchmark suites can include 100k+ products and multi-million-row operational/order fixtures where representative.

These are engineering benchmark targets, not blanket performance guarantees for every hosting stack.

---

## 89. Reliability patterns

Platform-wide patterns include:

- idempotency keys
- retry/backoff
- deduplication
- locks where required
- resumable migrations
- resumable bulk jobs
- reconciliation checks
- dead-work visibility
- provider circuit/failure isolation where appropriate

Example expectations:

- duplicated tracking webhook does not create duplicate side effects
- duplicated gift-card delivery job does not issue twice
- repeated receiving request does not receive stock twice
- failed 50,000-product job resumes rather than starts blindly from zero

---

# PART M — ACCESSIBILITY AND UX

## 90. Accessibility

Shopper and Hub components target WCAG 2.2 AA.

Requirements include:

- keyboard operation
- visible focus
- accessible dialogs/drawers
- labelled forms/errors
- accessible swatch/variant semantics
- non-colour-only state communication
- appropriate target sizes
- screen-reader meaningful status updates

A theme may change appearance without forcing replacement of accessible UC behaviour.

---

## 91. Hub UX principles

Hub must be easier than wp-admin for operational staff.

Principles:

- role/task-first navigation
- mobile/PWA-ready
- scan-first workflows
- fast lookup
- minimal jargon
- clear success/error feedback
- offline-ish resilience only where safely designed; never pretend a stock mutation completed if the backend did not confirm it
- destructive/high-risk actions require explicit confirmation/approval

---

# PART N — PRIVACY, PORTABILITY AND LICENCE EXPIRY

## 92. Merchant data rights

Merchant data remains merchant-owned.

UC should provide export/import/portability for appropriate UC-owned concepts such as:

- configuration
- product relationships
- campaigns/rules
- inventory movements
- locations/transfers
- gift-card/stored-value history subject to security controls
- returns
- stock alerts
- supplier/purchase-order data

Secrets are excluded from ordinary configuration exports.

Licence expiry never makes historical operational data inaccessible merely to coerce renewal.

---

# PART O — DEVELOPER PLATFORM

## 93. Public extension surface

Agencies and third parties should be able to extend UC through stable, documented contracts.

Over time this includes:

- PHP interfaces/services
- hooks/filters
- REST APIs
- Woo Store API extensions
- provider contracts
- internal event vocabulary
- template/slot contracts
- webhooks where appropriate
- WP-CLI

Pro itself should use the same public contracts where practical.

Breaking public contracts require semver/deprecation discipline.

---

## 94. Compatibility strategy

CI/QA should cover, as claimed:

- minimum supported PHP/WordPress/WooCommerce
- current stable WordPress/WooCommerce
- HPOS enabled
- Cart/Checkout Blocks
- relevant classic compatibility only where intentionally supported
- common caching/object-cache conditions
- multilingual/multi-currency integrations over time through documented compatibility tests

Compatibility metadata must reflect testing, not aspiration.

---

# PART P — OBSERVABILITY AND SUPPORTABILITY

## 95. Diagnostics

System status should expose, without leaking secrets:

- versions
- module state
- schema/migration status
- queue health
- Woo/HPOS state
- integration health
- reconciliation warnings
- failed background work
- compatibility information

Structured logs should include stable context/entity/provider/job identifiers.

---

## 96. Operational failure rules

- tracking outage does not block checkout
- recommendation outage falls back safely
- notification failure does not duplicate gift-card issuance
- search outage has a defined fallback where possible
- provider timeouts are bounded
- failed automation does not recursively retry forever
- stock/gift-card concurrency failures fail closed rather than silently corrupt data

---

# PART Q — COMMERCIAL EXPERIENCE AND AGENCY STRATEGY

## 97. Agency-first qualities

Ultimate Commerce should support agencies through:

- staging-safe licensing
- configuration export/import
- predictable semantic contracts
- no theme lock-in
- documentation/reference implementations
- WP-CLI over time
- repeatable deployment
- compatibility matrix
- clean upgrade/migration behaviour

An agency standardising on UC across many stores is a major distribution channel.

---

## 98. What creates the moat

Basic swatches, badges and cart drawers are acquisition features.

The long-term moat is the connected platform:

```text
customer profile
 -> availability
 -> recommendation
 -> purchase
 -> fulfilment
 -> return/fit outcome
 -> stock movement
 -> merchandising insight
 -> future recommendation/automation
```

And operationally:

```text
supplier / receipt
 -> inventory movement
 -> location availability
 -> merchandising
 -> customer purchase
 -> return
 -> replenishment insight
```

And financially:

```text
gift card purchase
 -> stored-value ledger
 -> redemption
 -> order
 -> return/store credit
 -> future purchase
```

This connected system is harder to replace with disconnected single-purpose plugins.

---

# PART R — ROADMAP PRINCIPLES

## 99. Build sequence

The master destination is broad; releases must be deliberately staged.

The high-level sequence is:

1. Free/Pro identity and security foundation
2. strong standalone Free catalogue/variant/cart product
3. WordPress.org Free launch
4. first sellable Pro: Advanced Variants + Conversion
5. rich merchandising: relationships, bundles, campaigns, badges/overlays/notices, recommendations
6. customer portal, My Size and stock intelligence
7. Hub foundation: staff app, catalogue operations and single-location inventory
8. multi-location, receiving, stocktake, transfers and purchasing
9. returns/exchanges/tracking maturity
10. gift cards, stored value and wallet
11. automation and cross-module intelligence
12. enterprise/agency hardening
13. hosted services only where economics justify them

`ROADMAP.md` owns the detailed phase gates and may reorder modules when product evidence justifies it, provided the architectural boundaries in this blueprint are preserved.

---

## 100. Feature prioritisation test

Before prioritising a feature, ask:

> Does this materially improve adoption, merchant revenue, customer retention, operational efficiency, security, scalability or platform defensibility?

If not, it should not displace work that does.

---

## 101. Definition of success

Ultimate Commerce succeeds when:

- a merchant can install Free and materially improve WooCommerce without payment
- a Pro merchant can replace a collection of disconnected premium plugins with one coherent platform
- retail staff can run common catalogue/inventory/aftercare workflows through Hub without entering WordPress admin
- WooCommerce remains authoritative for commerce truth
- UC-owned operational data is secure, auditable and portable
- agencies can extend/deploy the platform through stable contracts
- large sites can trust the security, performance and migration discipline
- each enabled module strengthens the usefulness of the others

The north-star product statement is:

> **WooCommerce owns commerce truth. Ultimate Commerce turns that truth into a richer selling, merchandising, customer-service and retail-operations platform.**
