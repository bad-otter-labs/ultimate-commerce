<?php

namespace BadOtter\UltimateCommerce\Storefront;

defined('ABSPATH') || exit;

final class CatalogQuery
{
    public const MAX_INCLUDE_IDS = 100;

    /** @return array<string, mixed>|\WP_Error */
    public static function query(array $input = array())
    {
        if (!class_exists('Automattic\\WooCommerce\\StoreApi\\Utilities\\ProductQuery') || !class_exists('WP_REST_Request')) {
            return new \WP_Error(
                'uc_catalog_unavailable',
                __('WooCommerce Store API product querying is unavailable.', 'ultimate-commerce-for-woocommerce'),
                array('status' => 503)
            );
        }

        $state = CatalogFilterRegistry::normalize($input);
        $params = self::storeApiParams($state, $input);
        $params = apply_filters('ultimate_commerce_catalog_store_api_params', $params, $state, $input);
        $params = self::reboundParams(is_array($params) ? $params : array(), $state);

        $request = new \WP_REST_Request('GET', '/wc/store/v1/products');
        $request->set_query_params($params);

        try {
            $productQuery = new \Automattic\WooCommerce\StoreApi\Utilities\ProductQuery();
            $results = $productQuery->get_results($request);
        } catch (\Throwable $exception) {
            return new \WP_Error(
                'uc_catalog_query_failed',
                __('The product catalogue could not be queried.', 'ultimate-commerce-for-woocommerce'),
                array('status' => 500)
            );
        }

        $ids = array_values(array_filter(array_map('absint', (array) ($results['results'] ?? array()))));
        $ids = array_slice($ids, 0, (int) $state['per_page']);
        self::primeProducts($ids);

        $products = array();
        foreach ($ids as $id) {
            $product = wc_get_product($id);
            if ($product instanceof \WC_Product && $product->is_visible()) {
                $products[] = $product;
            }
        }

        $variationContext = VariationViewModel::primeForProducts($products);
        self::primeMedia($products, $variationContext);

        $items = array();
        foreach ($products as $product) {
            $items[] = ProductCardViewModel::fromProduct($product, array('variation_context' => $variationContext));
        }

        $lastModified = method_exists($productQuery, 'get_last_modified') ? $productQuery->get_last_modified() : null;
        $cacheContext = array(
            'currency' => function_exists('get_woocommerce_currency') ? get_woocommerce_currency() : '',
            'tax_display' => (string) get_option('woocommerce_tax_display_shop', ''),
            'locale' => function_exists('determine_locale') ? determine_locale() : get_locale(),
        );
        $cacheContext = apply_filters('ultimate_commerce_catalog_cache_context', $cacheContext, $state, $input);
        if (!is_array($cacheContext)) {
            $cacheContext = array();
        }
        $publicCacheSafe = (bool) apply_filters(
            'ultimate_commerce_catalog_public_cache_safe',
            false,
            $state,
            $input,
            $cacheContext
        );

        $payload = array(
            'items' => $items,
            'pagination' => array(
                'page' => (int) $state['page'],
                'per_page' => (int) $state['per_page'],
                'total' => (int) ($results['total'] ?? 0),
                'pages' => max(1, (int) ($results['pages'] ?? 1)),
            ),
            'state' => $state,
            'filters' => CatalogFilterRegistry::describe($state),
            'canonical_query' => CatalogFilterRegistry::canonicalQuery($state),
            'cache' => array(
                'public_cache_safe' => $publicCacheSafe,
                'last_modified' => is_string($lastModified) ? $lastModified : null,
                'context' => $cacheContext,
                'key' => hash('sha256', (string) wp_json_encode(array($state, $cacheContext, $lastModified))),
            ),
        );

        $filtered = apply_filters('ultimate_commerce_catalog_query_result', $payload, $state, $input);
        return is_array($filtered) ? $filtered : $payload;
    }

    /** @return array<string, mixed> */
    private static function storeApiParams(array $state, array $input): array
    {
        $include = array_values(array_unique(array_filter(array_map('absint', (array) ($input['include'] ?? array())))));
        $include = array_slice($include, 0, self::MAX_INCLUDE_IDS);
        $sort = CatalogFilterRegistry::sortDefinition((string) $state['sort']);
        $params = array(
            'offset' => 0,
            'order' => strtoupper((string) ($sort['order'] ?? 'DESC')),
            'orderby' => (string) ($sort['orderby'] ?? 'date'),
            'page' => (int) $state['page'],
            'include' => $include,
            // phpcs:ignore WordPressVIPMinimum.Performance.WPQueryParams.PostNotIn_exclude -- Empty Woo Store API schema default; no exclusion query is executed unless an extension supplies bounded IDs.
            'exclude' => array(),
            'per_page' => (int) $state['per_page'],
            'parent' => array(),
            'parent_exclude' => array(),
            'search' => '',
            'slug' => array(),
            'sku' => array(),
            'catalog_visibility' => 'visible',
            'attributes' => array(),
            'attribute_relation' => 'and',
        );

        if ($include !== array() && !isset($input['sort'])) {
            $params['orderby'] = 'include';
            $params['order'] = 'ASC';
        }

        foreach ((array) ($state['filters'] ?? array()) as $id => $values) {
            $definition = CatalogFilterRegistry::definition((string) $id);
            if (!$definition || ($definition['type'] ?? '') !== 'taxonomy') {
                continue;
            }
            $taxonomy = (string) $definition['taxonomy'];
            $values = array_values((array) $values);
            if (str_starts_with($taxonomy, 'pa_')) {
                $params['attributes'][] = array(
                    'attribute' => $taxonomy,
                    'slug' => $values,
                    'operator' => 'in',
                );
            } elseif ($taxonomy === 'product_cat') {
                $params['category'] = $values;
                $params['category_operator'] = 'in';
            } elseif ($taxonomy === 'product_tag') {
                $params['tag'] = $values;
                $params['tag_operator'] = 'in';
            } elseif ($taxonomy === 'product_brand') {
                $params['brand'] = $values;
                $params['brand_operator'] = 'in';
            } else {
                $params['_unstable_tax_' . $taxonomy] = $values;
                $params['_unstable_tax_' . $taxonomy . '_operator'] = 'in';
            }
        }

        $scale = 10 ** wc_get_price_decimals();
        if (($state['price']['min'] ?? null) !== null) {
            $params['min_price'] = (string) (int) round((float) $state['price']['min'] * $scale);
        }
        if (($state['price']['max'] ?? null) !== null) {
            $params['max_price'] = (string) (int) round((float) $state['price']['max'] * $scale);
        }

        $stockMap = array(
            'in_stock' => 'instock',
            'out_of_stock' => 'outofstock',
            'on_backorder' => 'onbackorder',
        );
        $stock = array();
        foreach ((array) ($state['availability'] ?? array()) as $value) {
            if (isset($stockMap[$value])) {
                $stock[] = $stockMap[$value];
            }
        }
        if ($stock !== array()) {
            $params['stock_status'] = array_values(array_unique($stock));
        }

        return $params;
    }

    /** @return array<string, mixed> */
    private static function reboundParams(array $params, array $state): array
    {
        $params['page'] = max(1, min(CatalogFilterRegistry::MAX_PAGE, (int) ($params['page'] ?? $state['page'])));
        $params['per_page'] = max(1, min(CatalogFilterRegistry::MAX_PER_PAGE, (int) ($params['per_page'] ?? $state['per_page'])));
        $params['include'] = array_slice(array_values(array_unique(array_filter(array_map('absint', (array) ($params['include'] ?? array()))))), 0, self::MAX_INCLUDE_IDS);
        // phpcs:ignore WordPressVIPMinimum.Performance.WPQueryParams.PostNotIn_exclude -- Public Store API extension input is normalized and capped at MAX_INCLUDE_IDS before WooCommerce receives it.
        $params['exclude'] = array_slice(array_values(array_unique(array_filter(array_map('absint', (array) ($params['exclude'] ?? array()))))), 0, self::MAX_INCLUDE_IDS);
        return $params;
    }

    private static function primeProducts(array $ids): void
    {
        if ($ids === array()) {
            return;
        }
        if (function_exists('_prime_post_caches')) {
            _prime_post_caches($ids, true, true);
        } else {
            update_meta_cache('post', $ids);
            update_object_term_cache($ids, 'product');
        }
    }

    /** @param array<int, \WC_Product> $products */
    private static function primeMedia(array $products, array $variationContext): void
    {
        $ids = array();
        foreach ($products as $product) {
            $ids[] = (int) $product->get_image_id();
            foreach ((array) $product->get_gallery_image_ids() as $imageId) {
                $ids[] = (int) $imageId;
            }
        }
        foreach ((array) ($variationContext['image_ids'] ?? array()) as $imageId) {
            $ids[] = (int) $imageId;
        }
        foreach ((array) ($variationContext['term_map'] ?? array()) as $terms) {
            foreach ((array) $terms as $term) {
                $ids[] = (int) ($term['swatch']['image_id'] ?? 0);
            }
        }
        $ids = array_values(array_unique(array_filter($ids)));
        if ($ids !== array() && function_exists('_prime_post_caches')) {
            _prime_post_caches($ids, false, true);
        }
    }
}
