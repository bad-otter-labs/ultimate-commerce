# Ultimate Commerce Free — completion and interface map

**24 November 2026 · DOC-02/03 candidate.** Supports the existing
[Foundation](../FOUNDATION.md), [family Blueprint](../BLUEPRINT.md),
[master Roadmap](../ROADMAP.md) and [Build Workflow](../BUILD-WORKFLOW.md).
It does not change product scope, price, entitlement, data authority,
publication or the existing phase numbers. Exact [main baseline
3b0692d5](https://github.com/bad-otter-labs/ultimate-commerce/tree/3b0692d5ad53eb4e89c3c15f3d86af6be60c03d1). The complete
returned 288-entry tree (37 Markdown files), selected source registrations,
contracts and workflow records were reviewed; exhaustive source/field/UX
review is still required.

## Identity, current state and release

- Canonical new development is in `packages/ultimate-commerce-for-woocommerce/`. Source
  header, version constant, readme Stable tag and Labs discovery manifest
  agree at **0.2.1**; the header/text domain is
  `bad-otter-ultimate-commerce-woocommerce`. The directory in source
  is not by itself the installed basename. Public plugin identity and
  actual update channel must follow the approved package/WordPress.org
  record, not older planned-slug wording.
- `packages/ultimate-commerce/` is the historical private updater
  migration bridge only; preserve its installed-data compatibility.
  Free contains no paid Pro implementation or external managed updater.
- [#60](https://github.com/bad-otter-labs/ultimate-commerce/pull/60), exact
  `f3844ac6fbf4b904d8c0957a0775a65937ee4a3d`, is the **0.2.2**
  Ready release candidate. Its PR records published Free 0.2.0 and a
  historical merchant 0.2.1 observation; neither is a fresh installed
  or directory readback. All 24 observed exact-head workflows succeeded,
  including Full 43206390990, public ZIP upgrade 43206391042,
  official Plugin Check 43206391120 and compatibility 43206391106.
  No new publication approval. Keep this documentation PR separate.
- Main Full 43023859212 and accessibility 43023859477 succeeded.
  Main commit records a disposable variable Quick Add to native Woo
  BACS checkout and Woo CRUD readback; no actual payment or merchant
  installation from that proof. The public-fork-safe Full Gate is
  GitHub-hosted; no separate Bad Otter Draft Fast exists in this source.
  Retain all applicable public trust and product/package checks.
- Pro [#101](https://github.com/bad-otter-labs/ultimate-commerce-pro/pull/101)
  is separate 0.2.6 discovery/CI repair. Three recent scheduled Pro-main
  integration runs failed (45033467590, 45234762398, 45451649078);
  latest job log unavailable. Candidate run 45408238454 actually passed
  route, live Free-main/Pro contract and aggregate; Ready Full
  45408698070 passed. This is not post-main incident closure or native
  stored-value DB/release proof.

## Actual source interfaces

Free declares six modules in [config/modules.php](https://github.com/bad-otter-labs/ultimate-commerce/blob/3b0692d5ad53eb4e89c3c15f3d86af6be60c03d1/packages/ultimate-commerce-for-woocommerce/config/modules.php):
Product Display, Variations, Cart, Wishlist, Recently Viewed and Account.
They extend the merchant's existing WooCommerce/WordPress routes, not a
new store URL namespace. WooCommerce owns product, variations, sellable
stock, prices, totals, orders, payment and refunds in standalone mode.
Theme-specific layout and all merchant branding remain outside Free.
The following explicit registrations and host surfaces were inspected;
enumerate runtime/theme hooks and effective callbacks before full acceptance.

| ID | Current entry / interface | Owner and remaining proof |
| --- | --- | --- |
| A01 | /wp-admin/admin.php?page=ultimate-commerce | Overview; VIEW_DIAGNOSTICS capability |
| A02 | page=ultimate-commerce-modules | Module enablement; MANAGE_SETTINGS, save nonce and missing/disabled dependency |
| A03 | page=ultimate-commerce-settings | Settings, bounded import/export/retention; MANAGE_SETTINGS |
| A04 | page=ultimate-commerce-diagnostics | Redacted health; VIEW_DIAGNOSTICS, no secret/private leak |
| A05 | admin-post badotulc_modules_save | Authenticated admin action; exact current capability, nonce, validation |
| A06 | admin-post badotulc_settings_export | Authorised non-secret export; no credentials |
| A07 | admin-post badotulc_settings_import | Authorised schema/version, validation, atomic safe change |
| A08 | admin-post badotulc_settings_retention | Authorised retention policy, explicit scope |
| I01 | GET /wp-json/ultimate-commerce/v1/wishlist | Signed-in wishlist; own user only |
| I02 | POST /wp-json/ultimate-commerce/v1/wishlist/items | Add valid product; own user, retry/limits |
| I03 | DELETE /wp-json/ultimate-commerce/v1/wishlist/items/{product_id} | Remove own item, invalid/foreign denial |
| I04 | POST /wp-json/ultimate-commerce/v1/wishlist/merge | Bounded guest-to-own-account merge |
| I05 | admin-ajax action badotulc_wishlist_bootstrap | Registered public/authenticated hooks; no-cache current own-state and nonce, guest empty state |
| I06 | ultimate_commerce_register_modules / ultimate_commerce_admin_menu | Public extension registry/menu contracts; Pro optional, exact compatible API and capabilities |
| I07 | Woo Store API wc/store/v1, cart drawer/footer | Native Woo cart authority; no second order/cart engine |

Sources: [AdminMenu](https://github.com/bad-otter-labs/ultimate-commerce/blob/3b0692d5ad53eb4e89c3c15f3d86af6be60c03d1/packages/ultimate-commerce-for-woocommerce/src/Admin/AdminMenu.php),
[ModulesPage](https://github.com/bad-otter-labs/ultimate-commerce/blob/3b0692d5ad53eb4e89c3c15f3d86af6be60c03d1/packages/ultimate-commerce-for-woocommerce/src/Admin/ModulesPage.php),
[SettingsPage](https://github.com/bad-otter-labs/ultimate-commerce/blob/3b0692d5ad53eb4e89c3c15f3d86af6be60c03d1/packages/ultimate-commerce-for-woocommerce/src/Admin/SettingsPage.php),
[Wishlist REST](https://github.com/bad-otter-labs/ultimate-commerce/blob/3b0692d5ad53eb4e89c3c15f3d86af6be60c03d1/packages/ultimate-commerce-for-woocommerce/src/Wishlist/WishlistRestController.php),
[Wishlist bootstrap](https://github.com/bad-otter-labs/ultimate-commerce/blob/3b0692d5ad53eb4e89c3c15f3d86af6be60c03d1/packages/ultimate-commerce-for-woocommerce/src/Wishlist/WishlistBootstrap.php),
[Cart drawer](https://github.com/bad-otter-labs/ultimate-commerce/blob/3b0692d5ad53eb4e89c3c15f3d86af6be60c03d1/packages/ultimate-commerce-for-woocommerce/src/Storefront/CartDrawer.php),
[plugin bootstrap](https://github.com/bad-otter-labs/ultimate-commerce/blob/3b0692d5ad53eb4e89c3c15f3d86af6be60c03d1/packages/ultimate-commerce-for-woocommerce/src/Plugin.php).
The root/Overview registration shares one slug. Registration is not
proof of effective authorisation; direct URLs, record scope, failed
nonce and access denial require actual callback/role tests.

### Customer journey and completion register

Actual shop/cart/checkout/account URLs are merchant-configured Woo routes.
Do not invent site-specific paths or assert current live availability.
Each workstream retains the master Roadmap's phases; below is a navigation
index, not a new phase sequence. Source present is not phase completion.

| ID | Surface / existing evidence | Remaining proof |
| --- | --- | --- |
| C01 | Catalogue/shop/category/search, product cards and opt-in filter/sort/page controls | Native Woo data, accurate counts, empty/ambiguous/invalid state, URLs, non-JS fallback and real responsive/keyboard test |
| C02 | Product detail, variations/swatches and Quick Add | Exact selected purchasable variation, price/availability, stale and invalid, no fabricated variation or stock |
| C03 | Cart drawer, native cart/checkout/order | Real Woo Store API, BACS disposable checkout proof, normal checkout fallback, race/error totals and WC Blocks/HPOS; no actual payment authority |
| C04 | Guest/signed-in wishlist | Browser-local vs own account, bounded merge, privacy export/erase, cache/no-token leak and revoke |
| C05 | Recently viewed | Browser-local only, native product data, bounded storage and privacy, unsupported/removed product |
| C06 | My Account/dashboard/order presentation | Authenticated own order via Woo CRUD, denied/guest, HPOS, no alternate customer/order owner |
| C07 | Admin overview/modules/settings/diagnostics | Exact capabilities/nonce, disabled dependencies, settings migration and safe import/export/uninstall |
| C08 | Security/audit/privacy/execution contracts | Scoped requests, CSRF, rate, replay, lock/idempotency, secret handling and fault/recovery; actual extension denial |
| C09 | Free/Pro and Labs optional discovery | Public APIs; Free remains useful alone, missing/disabled/revoked Pro, real checked source + published ZIP compatibility; #101/post-main proof |
| C10 | Canonical Free distribution and legacy migration | #60 exact review, official archive/basename/update readback, preserved data and no duplicate activation, directory acceptance and separate publishing |
| C11 | Design, content and support | Native theme fallback, no merchant-specific code, original rights and screenshot/translation, keyboard/mobile/accessibility and docs |
| C12 | Future family/Hub/central stock | Paid features in Pro, Hub included; no new issuer or central stock writer absent approved ADR, actual migration/rollback and independent review |

## Next work and evidence

Retain [ADR 0003](adr/0003-optional-labs-foundation-and-stock-authority.md):
read-only Labs discovery is optional and not stock/payment authority.
Continue Free/Pro product work without waiting for Builder or future
central stock/EPOS. Reconcile #60 separately; never republish changed
bytes under an already distributed version. Retain existing public
GitHub-hosted trust boundaries, exact source, actual required checks,
zero behind, independent review and authorised expected-head merge.
No release, visibility change, plugin install, real checkout or spend
from this documentation. See [responsibility index](../.badotter/documentation-index.json) for
27 unresolved records with exact Git blob evidence. Central DOC-01
[#116](https://github.com/bad-otter-labs/.github/pull/116) at
`ccbeec8d417d48432b4056a7ed365b9f72e559d1` remains a candidate, not active CI enforcement.
Quality 9/10 pending actual downstream Python validator, full semantic
review, independent review and current release/installation proof.
