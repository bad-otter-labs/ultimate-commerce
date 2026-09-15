# Ultimate Commerce Roadmap

Status: **Sequenced product roadmap**

This roadmap turns `BLUEPRINT.md` and `FOUNDATION.md` into an implementation order. It is intentionally outcome-based rather than date-based. A phase exits only when its technical, security and product gates are satisfied.

The roadmap is designed around three simultaneous goals:

1. build a genuinely useful WordPress.org Free product
2. create a premium Pro product with obvious merchant ROI
3. use FishingClothing.co.uk as the first serious proving ground without creating store-specific code paths

## Roadmap principles

- build the Free public contracts first; Pro extends them
- security/compliance is built in, not added at submission time
- prove each module on realistic WooCommerce data before broadening scope
- prioritise features that either drive adoption, generate merchant revenue or remove significant operational work
- do not create hosted-service cost until the local product has product-market evidence
- no major release is complete without an upgrade test from realistic existing data

---

## Phase 0 — Product split and constitutional reset

**Goal:** align the codebase with the Free/Pro/WordPress.org model before more feature code compounds the old assumptions.

### Deliverables

- lock public name: **Ultimate Commerce for WooCommerce**
- lock brand name: **Ultimate Commerce**
- reserve/prepare planned Free slug `ultimate-commerce-for-woocommerce`
- define `ultimate-commerce-pro` as the paid companion identity
- create the private `bad-otter-labs/ultimate-commerce-pro` repository
- ensure Free has no runtime dependency on Pro
- define the public extension/module registration contract Pro will consume
- define the migration path from the current private pre-release plugin identity to the WordPress.org Free identity before public installs exist
- remove the assumption that Free production updates come from Bad Otter
- retain Bad Otter managed updates for Pro
- reconcile `FOUNDATION.md`, `AGENTS.md`, `BUILD-WORKFLOW.md` and CI with `BLUEPRINT.md`

### Exit gate

- no architecture document contradicts the Free/Pro distribution model
- Free and Pro package identities are explicit
- Free can run independently with Pro absent
- Pro can register modules only through public Free contracts

### Commercial outcome

We avoid expensive identity/distribution migration after users exist and create the clean foundation required for WordPress.org acquisition.

---

## Phase 1 — Security and WordPress.org engineering baseline

**Goal:** make the development process capable of producing code safe enough for large merchants and reviewable by WordPress.org.

### Deliverables

- dedicated UC capability model
- reusable object-ownership authorisation helpers
- strict REST route/schema conventions
- shared CSRF/nonce patterns
- secure guest-token utility
- secret-store abstraction
- shared provider HTTP client conventions with SSRF protections
- shared signed webhook gateway with replay/idempotency controls
- structured audit-event contract
- privacy data classification rules
- WordPress personal-data exporter/eraser integration foundation
- Action Scheduler job conventions: IDs over PII snapshots, bounded retry, idempotency
- pagination/query-limit standards
- security-focused coding standards and review checklist

### CI / release gate

Add or prepare:

- Plugin Check
- PHPCS + WordPress Coding Standards
- PHP compatibility/static analysis
- dependency vulnerability scans
- secret scanning
- package inspection
- permission/ownership regression tests
- CSRF/REST schema tests
- HPOS compatibility tests
- Blocks compatibility tests

### Exit gate

A deliberately vulnerable fixture/test cannot bypass the expected permission, ownership, CSRF or request-schema boundaries.

### Commercial outcome

Security becomes part of the product, making future agency/enterprise adoption realistic instead of requiring a rewrite later.

---

## Phase 2 — Free storefront value: catalogue, variants and cart

**Goal:** create the first Free experience that a merchant would install even if Pro did not exist.

### Free modules

#### Catalogue/Product Cards

- reusable product view model
- reusable product-card contract
- media/secondary-media state
- brand/title/price/rating data contracts
- configurable card components
- manual badge support
- no N+1 catalogue queries

#### Variants & Swatches

- text/button selectors
- colour/image swatches
- selected state
- variation image switching
- available/unavailable state
- low-stock presentation
- URL/deep-link state where appropriate

#### Quick Add

- variation-aware quick add
- accessible dialog/state management
- stock-safe add-to-cart

#### Basic Cart Drawer

- Woo Store API/cart truth
- add/remove/update quantity
- accessible drawer/focus management
- loading/error state
- extension slots for Pro recommendations/incentives later

### FishingClothing proving slice

Prove:

```text
PLP product card
 -> variation/size selection
 -> quick add
 -> cart drawer
 -> Woo cart totals
 -> checkout
 -> real Woo order
```

### Exit gate

- performant against a large variable-product fixture
- keyboard/screen-reader core flow passes accessibility review
- HPOS/Blocks matrix green
- no store-specific rendering/business logic in UC

### Commercial outcome

Free becomes visibly valuable and begins providing the hooks Pro will monetise later.

---

## Phase 3 — Free retention and merchant usability

**Goal:** make Free feel complete rather than a demo of storefront components.

### Deliverables

- basic wishlist
- recently viewed
- basic stock/low-stock presentation
- basic account/order enhancement hooks/view models
- settings import/export
- mature diagnostics/system-status screen
- module enable/disable UX
- documentation for theme/store integration
- translation readiness
- uninstall/data-retention controls

### Exit gate

A normal WooCommerce merchant can configure and use Free without a Bad Otter account, custom code or external service.

### Commercial outcome

Free is strong enough for positive directory reviews, agency experimentation and organic adoption.

---

## Phase 4 — WordPress.org public beta and Free 1.0

**Goal:** turn Free into a directory-quality acquisition product.

### Submission preparation

- transition public package identity to `ultimate-commerce-for-woocommerce`
- remove Bad Otter updater code from the directory package
- public/reviewable Free source repository
- human-readable JS/CSS source and reproducible build instructions
- WordPress.org readme/screenshots/assets
- clear privacy/external-service disclosures
- restrained Pro discovery/upgrade UI
- translation template
- support/documentation links

### Mandatory release gate

- Plugin Check: no blocking errors
- WPCS/PHPCS green
- dependency/security scans green
- no unsolicited telemetry
- no remote executable/static dependencies for normal local Free features
- package contains production files only
- tested minimum/current WordPress + WooCommerce matrix
- HPOS and Blocks green
- accessibility baseline green
- upgrade from pre-release data fixture validated

### Exit gate

WordPress.org submission accepted or all reviewer feedback is resolved with no architecture compromise.

### Commercial outcome

Free becomes the top-of-funnel channel for Ultimate Commerce Pro.

---

## Phase 5 — Pro foundation and first paid conversion pack

**Goal:** ship a paid product with enough immediate ROI to justify an annual licence.

### Pro platform

- Pro dependency guard requiring Free
- Bad Otter entitlement/update client
- staging/development licence treatment
- Pro module registry integration through Free public contracts
- safe expiry behaviour
- Pro diagnostics without leaking licence secrets

### First Pro modules

#### Advanced Variants

- linked/sibling colours
- colour families
- size availability on cards
- preferred size
- `Available in my size` contract
- advanced variation/linked-product strategies

#### Advanced Cart & Conversion

- free-shipping progress rules
- cart recommendations
- cross-sells
- complete-the-look slots
- configurable incentive messaging
- saved-cart foundation

### Exit gate

A merchant can install Free + Pro, activate a licence, receive a native managed update, disable Pro without corrupting Free data and demonstrate measurable conversion-oriented features on FishingClothing.

### Commercial outcome

**First sellable Ultimate Commerce Pro.**

Pricing experiment starts around the blueprint's £149–£199 single-site annual range.

---

## Phase 6 — Merchandising and recommendations

**Goal:** build the first major moat and strongest agency/merchant selling point.

### Dynamic Merchandising

- rule-based collections
- include/exclude/pin/boost
- stock/newness/sale rules
- schedules
- automated badges
- deterministic rule explanations
- stock-aware sorting

### Recommendations

- manual curation
- complementary products
- same brand/collection
- recently viewed
- frequently bought together from order data
- fallback strategy chains
- storefront placement contracts

### Analytics

- recommendation impressions/clicks/add-to-cart/purchase attribution
- collection performance
- merchant-owned reporting

### Exit gate

Merchants can explain why a product appears in a collection/recommendation and measure whether the feature contributes value.

### Commercial outcome

Pro moves from "better UX" to a **merchandising platform** with stronger recurring value.

---

## Phase 7 — Customer portal, My Size and stock intelligence

**Goal:** connect customer preferences to catalogue availability and retention.

### Customer Portal

- account overview
- visual order history/timeline
- wishlist/preferences
- stock alerts
- recently viewed
- communication preferences

### My Size / Commerce Profile

- merchant-defined profile attributes
- apparel presets
- preferred-size persistence
- `shop my size` availability/filter contracts
- consent/privacy controls

### Stock Intelligence

- variation-level back-in-stock alerts
- stock-aware recommendation/merchandising inputs
- demand/velocity signals
- low-stock/high-demand reporting

### Exit gate

Customer profile data is exportable/erasable, object-authorised and useful across at least two independent modules without creating a hard coupling.

### Commercial outcome

UC begins creating the cross-module data advantage described in the blueprint.

---

## Phase 8 — Returns, exchanges and tracking

**Goal:** make Pro materially reduce merchant service workload and improve post-purchase experience.

### Returns

- eligibility policy engine
- return request workflow
- line-item quantities
- reasons
- audit trail
- partial returns
- refund handoff to Woo/payment APIs

### Exchanges

- size/variation exchange
- inventory/reservation policy
- race-condition handling
- replacement order/adjustment strategy after dedicated architecture review

### Tracking

- normalised shipment/event model
- multiple shipments
- customer timeline
- provider contract
- first production courier adapters
- signed webhook/polling support

### Exit gate

End-to-end:

```text
Woo order
 -> shipment
 -> tracking timeline
 -> return request
 -> exchange/refund decision
 -> WooCommerce monetary/order handoff
```

with complete ownership and audit checks.

### Commercial outcome

Pro has a strong operational ROI story and can justify higher single-site pricing.

---

## Phase 9 — Promotions, automation and value reporting

**Goal:** deepen merchant automation without replacing WooCommerce calculation truth.

### Deliverables

- promotion presentation rules
- scheduled campaigns
- spend/category/brand eligibility messaging
- free-gift/bundle orchestration where calculation ownership is explicit
- notification provider contracts
- automation events/actions
- operational dashboards
- module value reporting
- return/fit/stock/merchandising analytics

### Exit gate

Promotion presentation never disagrees with authoritative Woo totals, and automations are idempotent/auditable.

### Commercial outcome

Pro becomes increasingly difficult to replace with disconnected single-purpose plugins.

---

## Phase 10 — Agency/enterprise hardening

**Goal:** make Ultimate Commerce credible on major stores and repeatable agency estates.

### Deliverables

- WP-CLI operational commands
- mature config deployment/import/export
- expanded role/capability presets
- performance benchmark suite
- large-catalogue/order fixtures
- object-cache/CDN compatibility guidance
- failure-injection/provider-outage testing
- migration interruption/recovery tests
- formal support/compatibility matrix
- security incident response process
- independent penetration/security review
- SBOM/release provenance where useful
- agency documentation/reference implementations

### Exit gate

Independent security review issues are resolved, documented scale targets are met and upgrades are proven against realistic large datasets.

### Commercial outcome

Agency/25-site licences and higher-value merchant conversations become credible.

---

## Phase 11 — Optional hosted services

**Goal:** add services only where hosted infrastructure creates value that cannot be delivered well as local WordPress code.

Potential services:

- hosted search
- AI/ML recommendations
- behavioural modelling
- communication delivery
- image/data enrichment
- large-scale analytics

### Rules

- separate service entitlement/usage from local Pro licence
- explicit merchant consent/data contracts
- clear degradation when service is unavailable
- no checkout dependency on a non-essential Bad Otter service
- transparent usage limits/costs

### Exit gate

Service economics are proven and the local Free/Pro plugins remain useful if the hosted service is absent.

---

# Recommended build priority from today

The immediate sequence should be:

1. **Phase 0:** complete product split/distribution architecture.
2. **Phase 1:** land security + WordPress.org CI foundations.
3. **Phase 2:** build the Free catalogue/variant/cart vertical slice and prove it on FishingClothing.
4. **Phase 3:** make Free complete enough to stand alone.
5. **Phase 4:** submit/launch Free on WordPress.org.
6. **Phase 5:** launch the first commercially sellable Pro with Advanced Variants + Advanced Cart.
7. **Phase 6:** prioritise Merchandising + Recommendations as the first major premium moat.
8. Build customer lifecycle and aftercare after the conversion/merchandising foundation is stable.

This sequence deliberately gets a strong Free acquisition product and a sellable Pro product into the market before attempting every long-term module.

# North-star release test

Before prioritising any roadmap item, ask:

> Does this materially increase adoption, merchant revenue, customer retention, operational efficiency, platform defensibility, security or scalability?

If not, it should not displace work that does.
