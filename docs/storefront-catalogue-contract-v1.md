# Storefront Catalogue Contract v1

Status: **public Free contract**

API version: `ULTIMATE_COMMERCE_STOREFRONT_API_VERSION = 1.0.0`

This contract is the supported theme/extension boundary for reusable product cards, bounded product lists, baseline variant state and catalogue filter state. WooCommerce remains authoritative for products, variations, prices, stock, visibility and cart mutations.

## Public PHP surface

- `BadOtter\UltimateCommerce\Storefront\Catalog::query(array $args)`
- `BadOtter\UltimateCommerce\Storefront\Catalog::product($product)`
- `BadOtter\UltimateCommerce\Storefront\Catalog::renderCard($product, array $context = [])`
- `BadOtter\UltimateCommerce\Storefront\Catalog::renderList(array $args = [], array $context = [])`
- `BadOtter\UltimateCommerce\Storefront\Catalog::filterState(array $query)`
- `BadOtter\UltimateCommerce\Storefront\ProductCardViewModel::fromProduct($product, array $context = [])`
- `BadOtter\UltimateCommerce\Storefront\ProductCardRenderer::enqueueAssets()`
- `BadOtter\UltimateCommerce\Storefront\VariationViewModel::forProduct($product, array $selected = [], array $context = [])`
- `BadOtter\UltimateCommerce\Storefront\VariationViewModel::fromVariation(WC_Product_Variation $variation)`
- `BadOtter\UltimateCommerce\Storefront\CatalogFilterRegistry`

Themes should check the API constant or `class_exists(Catalog::class)` before consuming the contract.

## Bounded product-list query

`Catalog::query()` accepts:

- `include`: explicit product IDs, maximum 100
- `page`: 1–500
- `per_page`: 1–48, default 24
- `sort`: `newest`, `price_asc`, `price_desc`
- `filter_<attribute>`: comma-separated or array values for registered product taxonomies
- `min_price` / `max_price`: non-negative Woo price values
- `availability`: `in_stock`, `out_of_stock`, `on_backorder`

The service translates validated state into WooCommerce Store API product-query parameters and delegates visibility, taxonomy/attribute, lookup-table price, stock and catalogue ordering semantics to WooCommerce. UC does not issue product-meta price queries or maintain a second stock/index table.

The response contains:

- `items`: product-card view models
- `pagination`: page/per-page/total/pages
- `state`: validated state shared by desktop/mobile consumers
- `filters`: labels, selected values and taxonomy term counts
- `canonical_query`: deterministic flat URL parameters
- `cache`: last-modified/context/cache-key metadata

The result contains no cart token, nonce or customer identity. UC conservatively reports `public_cache_safe=false` by default because guest prices can still vary by tax, market, geolocation or currency integrations. A deployment may opt in through `uc_catalog_public_cache_safe` only after `uc_catalog_cache_context` includes every required cache-vary dimension for that store. UC never assumes that logged-out implies globally cacheable.

## Product-card schema

`uc.product-card.v1` exposes:

- Woo product ID/type/SKU/name/URL
- primary and secondary image payloads
- Woo `product_brand` where available
- current/regular/sale price and Woo price HTML
- genuine Woo average rating/count when reviews exist
- Woo stock/purchasability/low-stock state plus sanitized Woo stock HTML in `availability.stock_html`
- baseline variable-product state
- Woo cart handoff metadata
- semantic classes and extension slots

Themes own CSS, layout, artwork and final placement. The default renderer intentionally ships semantic class names without brand styling.

## No N+1 catalogue loading

`CatalogQuery` first asks Woo's Store API `ProductQuery` for bounded product IDs, primes product post/meta/term caches as a batch, loads the page products once, queries all page variation posts in one bounded `post_parent__in` query, primes variation caches, derives selectable attributes from each parent's already-loaded `WC_Product_Attribute` declarations, batches attribute-term/meta lookup by taxonomy and primes attachment metadata before card construction.

The catalogue path deliberately does not call `WC_Product_Variable::get_variation_attributes()` per card because that helper can lazy-load child variation data.

Limits:

- 48 parent products per page
- 100 variations represented per product
- 1000 variation posts across one page
- 100 filter options exposed per taxonomy
- 12 selected values per filter

If variation limits are exceeded, `variation.truncated` is true and `variation.availability_complete` is false. UC then does not mark unseen combinations unavailable. A consumer must link to the product rather than assuming incomplete card state is exhaustive.

## Baseline variation state

`VariationViewModel::forProduct()` exposes:

- button/text options for global taxonomy attributes and local/custom Woo variation attributes
- image swatches when term meta `uc_swatch_image_id` is set
- colour swatches when term meta `uc_swatch_color` is set
- default/selected attributes
- option availability based on the bounded Woo variation set
- selected matching variation
- variation-specific image and price
- stock status, low-stock state and sanitized Woo stock HTML in `stock_html`
- `radiogroup`/`radio`, `aria-checked`, `aria-disabled` semantics
- Woo Store API add-item handoff metadata only when a complete selected variation is purchasable and in stock

The `uc_swatch_color` and `uc_swatch_image_id` term-meta keys are the built-in Free storage convention, not a requirement for external swatch systems. `uc_variation_swatch_data` receives the default `{color, image_id}` payload, the Woo term and taxonomy; an integration may map an existing swatch provider into the same public state without changing catalogue/product-card code. UC validates the returned colour and attachment ID again after the filter.

The default semantic renderer progressively enhances these states with `assets/js/variation-controls.js`. It owns reusable selection matching, option availability refresh, variation image/price/stock-presentation switching, submit-state changes and radio-style arrow-key navigation. The controller is loaded only when the default renderer outputs variable-product controls.

Stock presentation remains WooCommerce-owned. UC obtains customer-facing stock markup from WooCommerce's `wc_get_stock_html()`, sanitizes it with `wp_kses_post()`, exposes it as presentation state only, and renders it in the semantic `[data-uc-stock]` region. The variation controller swaps that Woo-generated markup when a concrete variation is selected and restores the parent product markup for incomplete selection. UC does not calculate, persist or mutate stock quantities/statuses.

For variation options, `value` is UC's normalized matching value. `request_value` is the value that must be handed back to Woo. They are identical for global taxonomy attributes (the term slug), while local/custom attributes preserve the original option case and spacing in `request_value` (for example `Regular Fit`) even though the matching value is normalized (for example `regular-fit`). The default renderer therefore keeps a non-named normalized state input for UC matching and a separate named `attribute_*` input carrying the exact Woo request value.

For its default rendered action, UC submits WooCommerce's native variable-product form fields (`add-to-cart`, `product_id`, `variation_id`, `attribute_*`, quantity) back to the product URL. WooCommerce therefore performs the authoritative stock/purchasability/cart validation on submission. UC does not create a second cart endpoint or mutate stock itself.

Custom theme renderers may use the PHP state directly. If they adopt UC's semantic variation markup/data attributes, they can call `ProductCardRenderer::enqueueAssets()` to use the same controller. They may instead hand the supplied identifiers to WooCommerce Store API/native Woo APIs; for local/custom attributes they must use each selected option's `request_value` rather than its normalized matching `value`. The mutation must remain Woo-owned.

The controller dispatches a bubbling `uc:variation-change` DOM event containing `productId`, normalized `selection`, the matched variation state and whether add-to-cart is enabled. This is presentation integration state only, not a second commerce truth.

## Filter configuration

Free automatically exposes Woo global product attributes (`pa_*`) plus price and availability. Stores can narrow, rename, reorder or add registered product taxonomies through `uc_catalog_filter_definitions`. The hook receives the complete registered global-attribute set first; UC applies the 16-filter safety cap after the store/extension has selected and reordered the definitions.

For large catalogues, dimensions such as garment type, use case, weather, waterproof rating, warmth, fit, size, colour and material should be modeled as Woo global attributes or other indexed product taxonomies. Do not model storefront facets as arbitrary product-meta scans.

Flat parameters such as `filter_colour=navy,olive&min_price=50&sort=price_asc&page=2` are deterministic. UC does not automatically emit canonical/noindex tags because indexation strategy is a store/SEO policy; the theme/SEO layer can use `canonical_query` when deciding canonical URLs.

`filters[].options[].count` is the bounded Woo taxonomy term count for the option. It is not presented as a fully faceted post-filter result count. A future count provider may refine counts through the public descriptor/query extension points without changing URL state.

`uc_catalog_filter_state` is an extension point, not a bypass around safety limits. UC revalidates the state after the hook, including page/per-page limits, selected-value caps, registered filter IDs, price ranges, availability values and sort identifiers.

## Public extension points

Filters:

- `uc_catalog_filter_definitions`
- `uc_catalog_sort_definitions`
- `uc_catalog_filter_state`
- `uc_catalog_filter_descriptors`
- `uc_catalog_store_api_params`
- `uc_catalog_query_result`
- `uc_catalog_cache_context`
- `uc_catalog_public_cache_safe`
- `uc_product_card_view_model`
- `uc_product_card_classes`
- `uc_variation_product_state`
- `uc_variation_state`
- `uc_variation_attribute_display`
- `uc_variation_swatch_data`

Compatibility filters:

- `uc_product_view_model`
- `uc_variation_view_model`

Actions:

- `uc_product_list_before`
- `uc_product_list_after`
- `uc_product_card_render_before`
- `uc_product_card_render_after`
- `uc_product_card_slot`

Browser event:

- `uc:variation-change`

Pro may add sort definitions such as Recommended, waterproof rating, warmth or Available in My Size and adapt the Store API request through the same public filters. Free contains no Pro ranking or My Size implementation.

## Historical bridge migration

The historical filters `uc_product_view_model` and `uc_variation_view_model` remain active compatibility extension points in v1, but they are no longer the preferred service entry point.

Migration:

1. Replace direct construction/use of `Modules\ProductDisplay\ProductViewModel` with `Storefront\Catalog::product()` or `ProductCardViewModel::fromProduct()`.
2. Replace calls to `VariationsModule::state()` with `VariationViewModel::fromVariation()` or `VariationViewModel::forProduct()` for full variable-product state.
3. Move new product-card custom fields to `uc_product_card_view_model`; the historical `uc_product_view_model` still runs after it for transition compatibility.
4. Move new single-variation custom fields to `uc_variation_state`; the historical `uc_variation_view_model` still runs after it.
5. For full product-level variation state/extensions, use `uc_variation_product_state` rather than assembling sibling variation queries in a theme.
6. If an existing plugin owns colour/image swatch metadata, map it once through `uc_variation_swatch_data` rather than reading that plugin's storage from theme templates.
7. Do not query variation IDs/stock/prices separately in the theme. Consume the UC model and hand cart mutation back to Woo.

## Example theme consumer

```php
use BadOtter\UltimateCommerce\Storefront\Catalog;

if (defined('ULTIMATE_COMMERCE_STOREFRONT_API_VERSION') && class_exists(Catalog::class)) {
    $result = Catalog::query(array(
        'include' => array(101, 102, 103, 104),
        'per_page' => 4,
    ));

    if (!is_wp_error($result)) {
        foreach ($result['items'] as $card) {
            echo Catalog::renderCard($card); // UC escapes its default renderer.
        }
    }
}
```

A custom theme may render `$card` itself instead of using the default renderer. The default renderer requires no UC-specific CSS; its class/slot names exist so the theme can supply presentation.

## UC-disabled behaviour

UC does not install shims into a theme. Consumers must guard the class/API constant and fall back to normal WooCommerce loops/templates when UC is absent. No UC-owned product or cart truth must be required for the store to remain operable.
