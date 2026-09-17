<?php

namespace BadOtter\UltimateCommerce\Storefront;

defined('ABSPATH') || exit;

final class VariationViewModel
{
    public const MAX_VARIATIONS_PER_PRODUCT = 100;
    public const MAX_VARIATIONS_PER_PAGE = 1000;

    /** @param array<int, \WC_Product> $products @return array<string, mixed> */
    public static function primeForProducts(array $products): array
    {
        $variableProducts = array_values(array_filter($products, static fn($product): bool => $product instanceof \WC_Product_Variable));
        $context = array('by_product' => array(), 'term_map' => array(), 'image_ids' => array(), 'truncated' => false);
        if ($variableProducts === array()) {
            return $context;
        }

        $parentIds = array_map(static fn(\WC_Product_Variable $product): int => $product->get_id(), $variableProducts);
        foreach ($parentIds as $parentId) {
            $context['by_product'][$parentId] = array('variations' => array(), 'variation_ids' => array(), 'truncated' => false);
        }

        $posts = get_posts(array(
            'post_type' => 'product_variation',
            'post_status' => 'publish',
            'post_parent__in' => $parentIds,
            'posts_per_page' => self::MAX_VARIATIONS_PER_PAGE + 1,
            'orderby' => array('menu_order' => 'ASC', 'ID' => 'ASC'),
            'no_found_rows' => true,
            'suppress_filters' => false,
        ));

        if (count($posts) > self::MAX_VARIATIONS_PER_PAGE) {
            $context['truncated'] = true;
            $posts = array_slice($posts, 0, self::MAX_VARIATIONS_PER_PAGE);
        }

        $variationIds = array_map(static fn($post): int => (int) $post->ID, $posts);
        if ($variationIds !== array() && function_exists('_prime_post_caches')) {
            _prime_post_caches($variationIds, false, true);
        }

        foreach ($posts as $post) {
            $parentId = (int) $post->post_parent;
            if (!isset($context['by_product'][$parentId])) {
                continue;
            }
            if (count($context['by_product'][$parentId]['variation_ids']) >= self::MAX_VARIATIONS_PER_PRODUCT) {
                $context['by_product'][$parentId]['truncated'] = true;
                continue;
            }
            $variation = wc_get_product((int) $post->ID);
            if (!$variation instanceof \WC_Product_Variation) {
                continue;
            }
            $context['by_product'][$parentId]['variation_ids'][] = $variation->get_id();
            $context['by_product'][$parentId]['variations'][] = $variation;
            $imageId = (int) $variation->get_image_id();
            if ($imageId > 0) {
                $context['image_ids'][] = $imageId;
            }
        }

        $context['image_ids'] = array_values(array_unique($context['image_ids']));
        $context['term_map'] = self::primeTermMap($variableProducts);
        return $context;
    }

    /**
     * @param int|\WC_Product $product
     * @return array<string, mixed>
     */
    public static function forProduct($product, array $selected = array(), array $context = array()): array
    {
        $product = is_numeric($product) ? wc_get_product((int) $product) : $product;
        if (!$product instanceof \WC_Product_Variable) {
            return array('variable' => false, 'attributes' => array(), 'variations' => array(), 'action' => array());
        }

        if (!isset($context['by_product'][$product->get_id()])) {
            $context = self::primeForProducts(array($product));
        }
        $productContext = (array) ($context['by_product'][$product->get_id()] ?? array());
        $variations = array_values(array_filter((array) ($productContext['variations'] ?? array()), static fn($variation): bool => $variation instanceof \WC_Product_Variation));
        $availabilityComplete = empty($productContext['truncated']) && empty($context['truncated']);
        $selection = self::normalizeSelection(array_merge($product->get_default_attributes(), $selected));
        $declaredAttributes = self::declaredAttributes($product, (array) ($context['term_map'] ?? array()));
        $attributes = array();

        foreach ($declaredAttributes as $declared) {
            $attributeName = (string) $declared['name'];
            $attributeOptions = array();
            foreach ((array) $declared['options'] as $declaredOption) {
                $option = (string) ($declaredOption['value'] ?? '');
                if ($option === '') {
                    continue;
                }
                $requestValue = (string) ($declaredOption['request_value'] ?? $option);
                $swatch = (array) ($declaredOption['swatch'] ?? array());
                $imageId = (int) ($swatch['image_id'] ?? 0);
                $available = self::optionAvailable($variations, $selection, $attributeName, $option, $availabilityComplete);
                $selectedOption = (($selection[$attributeName] ?? '') === $option);
                $attributeOptions[] = array(
                    'value' => $option,
                    'request_value' => $requestValue,
                    'label' => (string) ($declaredOption['label'] ?? $option),
                    'selected' => $selectedOption,
                    'available' => $available,
                    'swatch' => array(
                        'color' => (string) ($swatch['color'] ?? ''),
                        'image' => $imageId > 0 ? MediaViewModel::fromAttachment($imageId, 'thumbnail') : null,
                    ),
                    'a11y' => array(
                        'role' => 'radio',
                        'aria_checked' => $selectedOption ? 'true' : 'false',
                        'aria_disabled' => $available ? 'false' : 'true',
                        'label' => (string) ($declaredOption['label'] ?? $option),
                    ),
                );
            }

            $display = 'button';
            foreach ($attributeOptions as $optionState) {
                if (!empty($optionState['swatch']['image'])) {
                    $display = 'image';
                    break;
                }
                if (!empty($optionState['swatch']['color'])) {
                    $display = 'color';
                }
            }
            $display = sanitize_key((string) apply_filters('uc_variation_attribute_display', $display, $attributeName, $product, $attributeOptions));
            if (!in_array($display, array('button', 'color', 'image'), true)) {
                $display = 'button';
            }

            $attributes[] = array(
                'name' => $attributeName,
                'label' => (string) $declared['label'],
                'display' => $display,
                'selected' => (string) ($selection[$attributeName] ?? ''),
                'options' => $attributeOptions,
                'a11y' => array(
                    'role' => 'radiogroup',
                    'label' => (string) $declared['label'],
                ),
            );
        }

        $complete = true;
        foreach ($declaredAttributes as $declared) {
            if (($selection[(string) $declared['name']] ?? '') === '') {
                $complete = false;
                break;
            }
        }
        $matched = $complete ? self::matchingVariation($variations, $selection) : null;
        $matchedState = $matched instanceof \WC_Product_Variation ? self::fromVariation($matched) : null;
        $enabled = $matched instanceof \WC_Product_Variation && $matched->is_purchasable() && $matched->is_in_stock();

        $action = array(
            'type' => $enabled ? 'add_to_cart' : 'select_options',
            'enabled' => $enabled,
            'label' => $enabled ? __('Add to cart', 'ultimate-commerce-for-woocommerce') : __('Select options', 'ultimate-commerce-for-woocommerce'),
            'product_id' => $product->get_id(),
            'variation_id' => $matched instanceof \WC_Product_Variation ? $matched->get_id() : 0,
            'attributes' => $selection,
            'url' => $product->get_permalink(),
            'store_api' => $enabled ? array(
                'route' => '/wc/store/v1/cart/add-item',
                'id' => $matched->get_id(),
                'quantity' => 1,
            ) : null,
            'stock_truth' => 'woocommerce',
        );

        $state = array(
            'variable' => true,
            'product_id' => $product->get_id(),
            'attributes' => $attributes,
            'selection' => $selection,
            'selection_complete' => $complete,
            'selected_variation' => $matchedState,
            'variations' => array_map(array(__CLASS__, 'fromVariation'), $variations),
            'variation_ids' => array_map(static fn(\WC_Product_Variation $variation): int => $variation->get_id(), $variations),
            'truncated' => !$availabilityComplete,
            'availability_complete' => $availabilityComplete,
            'price' => $matchedState['price'] ?? array(
                'current' => (string) $product->get_price(),
                'regular' => (string) $product->get_regular_price(),
                'sale' => (string) $product->get_sale_price(),
                'html' => $product->get_price_html(),
                'on_sale' => $product->is_on_sale(),
            ),
            'media' => $matchedState['media'] ?? null,
            'action' => $action,
        );

        $filtered = apply_filters('uc_variation_product_state', $state, $product);
        return is_array($filtered) ? $filtered : $state;
    }

    /** @return array<string, mixed> */
    public static function fromVariation(\WC_Product_Variation $variation): array
    {
        $state = array(
            'id' => $variation->get_id(),
            'attributes' => self::normalizeSelection($variation->get_attributes()),
            'purchasable' => $variation->is_purchasable(),
            'in_stock' => $variation->is_in_stock(),
            'stock_status' => $variation->get_stock_status(),
            'stock_quantity' => $variation->get_stock_quantity(),
            'low_stock' => self::isLowStock($variation),
            'price' => array(
                'current' => (string) $variation->get_price(),
                'regular' => (string) $variation->get_regular_price(),
                'sale' => (string) $variation->get_sale_price(),
                'html' => $variation->get_price_html(),
                'on_sale' => $variation->is_on_sale(),
            ),
            'media' => MediaViewModel::fromAttachment((int) $variation->get_image_id()),
        );

        $state = apply_filters('uc_variation_state', $state, $variation);
        $state = is_array($state) ? $state : array();
        $legacy = apply_filters('uc_variation_view_model', $state, $variation);
        return is_array($legacy) ? $legacy : $state;
    }

    /** @param array<int, \WC_Product_Variable> $products @return array<string, array<string, array<string, mixed>>> */
    private static function primeTermMap(array $products): array
    {
        $termIdsByTaxonomy = array();
        foreach ($products as $product) {
            foreach ((array) $product->get_attributes() as $attribute) {
                if (!$attribute instanceof \WC_Product_Attribute || !$attribute->get_variation() || !$attribute->is_taxonomy()) {
                    continue;
                }
                $taxonomy = self::attributeKey($attribute->get_name());
                if (!taxonomy_exists($taxonomy)) {
                    continue;
                }
                foreach (array_slice((array) $attribute->get_options(), 0, CatalogFilterRegistry::MAX_OPTIONS_PER_FILTER) as $termId) {
                    $termId = absint($termId);
                    if ($termId > 0) {
                        $termIdsByTaxonomy[$taxonomy][$termId] = true;
                    }
                }
            }
        }

        $map = array();
        foreach ($termIdsByTaxonomy as $taxonomy => $termIds) {
            $terms = get_terms(array(
                'taxonomy' => $taxonomy,
                'hide_empty' => false,
                'include' => array_keys($termIds),
                'number' => CatalogFilterRegistry::MAX_OPTIONS_PER_FILTER,
            ));
            if (is_wp_error($terms)) {
                continue;
            }
            $resolvedIds = array_map(static fn($term): int => (int) $term->term_id, (array) $terms);
            if ($resolvedIds !== array() && function_exists('update_termmeta_cache')) {
                update_termmeta_cache($resolvedIds);
            }
            foreach ((array) $terms as $term) {
                $defaultSwatch = array(
                    'color' => (string) get_term_meta((int) $term->term_id, 'uc_swatch_color', true),
                    'image_id' => absint(get_term_meta((int) $term->term_id, 'uc_swatch_image_id', true)),
                );
                $filteredSwatch = apply_filters('uc_variation_swatch_data', $defaultSwatch, $term, $taxonomy);
                $filteredSwatch = is_array($filteredSwatch) ? $filteredSwatch : $defaultSwatch;
                $color = sanitize_hex_color((string) ($filteredSwatch['color'] ?? ''));
                $imageId = absint($filteredSwatch['image_id'] ?? 0);
                $map[$taxonomy][(string) $term->slug] = array(
                    'id' => (int) $term->term_id,
                    'label' => (string) $term->name,
                    'slug' => (string) $term->slug,
                    'swatch' => array(
                        'color' => is_string($color) ? $color : '',
                        'image_id' => $imageId,
                    ),
                );
            }
        }
        return $map;
    }

    /** @return array<int, array<string, mixed>> */
    private static function declaredAttributes(\WC_Product_Variable $product, array $termMap): array
    {
        $declared = array();
        foreach ((array) $product->get_attributes() as $attribute) {
            if (!$attribute instanceof \WC_Product_Attribute || !$attribute->get_variation()) {
                continue;
            }
            $rawName = (string) $attribute->get_name();
            $name = $attribute->is_taxonomy() ? self::attributeKey($rawName) : sanitize_title($rawName);
            if ($name === '') {
                continue;
            }
            $options = array();
            if ($attribute->is_taxonomy()) {
                $allowedIds = array_flip(array_map('absint', array_slice((array) $attribute->get_options(), 0, CatalogFilterRegistry::MAX_OPTIONS_PER_FILTER)));
                foreach ((array) ($termMap[$name] ?? array()) as $term) {
                    $termId = (int) ($term['id'] ?? 0);
                    if ($termId <= 0 || !isset($allowedIds[$termId])) {
                        continue;
                    }
                    $slug = (string) ($term['slug'] ?? '');
                    $options[] = array(
                        'value' => $slug,
                        'request_value' => $slug,
                        'label' => (string) ($term['label'] ?? ''),
                        'swatch' => (array) ($term['swatch'] ?? array()),
                    );
                }
            } else {
                foreach (array_slice((array) $attribute->get_options(), 0, CatalogFilterRegistry::MAX_OPTIONS_PER_FILTER) as $option) {
                    if (!is_scalar($option)) {
                        continue;
                    }
                    $requestValue = (string) $option;
                    $value = sanitize_title($requestValue);
                    if ($value === '') {
                        continue;
                    }
                    $options[] = array(
                        'value' => $value,
                        'request_value' => $requestValue,
                        'label' => $requestValue,
                        'swatch' => array(),
                    );
                }
            }
            if ($options === array()) {
                continue;
            }
            $declared[] = array(
                'name' => $name,
                'label' => $attribute->is_taxonomy() ? wc_attribute_label($rawName, $product) : $rawName,
                'options' => $options,
            );
        }
        return $declared;
    }

    /** @param array<int, \WC_Product_Variation> $variations */
    private static function optionAvailable(array $variations, array $selection, string $attribute, string $option, bool $completeData): bool
    {
        if (!$completeData) {
            return true;
        }
        foreach ($variations as $variation) {
            if (!$variation->is_purchasable() || !$variation->is_in_stock()) {
                continue;
            }
            $attributes = self::normalizeSelection($variation->get_attributes());
            $candidate = (string) ($attributes[$attribute] ?? '');
            if ($candidate !== '' && $candidate !== $option) {
                continue;
            }
            $matches = true;
            foreach ($selection as $key => $selected) {
                if ($key === $attribute || $selected === '') {
                    continue;
                }
                $variationValue = (string) ($attributes[$key] ?? '');
                if ($variationValue !== '' && $variationValue !== $selected) {
                    $matches = false;
                    break;
                }
            }
            if ($matches) {
                return true;
            }
        }
        return false;
    }

    /** @param array<int, \WC_Product_Variation> $variations */
    private static function matchingVariation(array $variations, array $selection): ?\WC_Product_Variation
    {
        foreach ($variations as $variation) {
            $attributes = self::normalizeSelection($variation->get_attributes());
            $matches = true;
            foreach ($selection as $key => $selected) {
                $variationValue = (string) ($attributes[$key] ?? '');
                if ($selected !== '' && $variationValue !== '' && $variationValue !== $selected) {
                    $matches = false;
                    break;
                }
            }
            if ($matches) {
                return $variation;
            }
        }
        return null;
    }

    /** @param array<string, mixed> $selection @return array<string, string> */
    private static function normalizeSelection(array $selection): array
    {
        $normalized = array();
        foreach ($selection as $key => $value) {
            if (!is_scalar($value)) {
                continue;
            }
            $normalized[self::attributeKey((string) $key)] = sanitize_title((string) $value);
        }
        return $normalized;
    }

    private static function attributeKey(string $key): string
    {
        return sanitize_key(str_starts_with($key, 'attribute_') ? substr($key, 10) : $key);
    }

    private static function isLowStock(\WC_Product $product): bool
    {
        $quantity = $product->get_stock_quantity();
        if ($quantity === null || $quantity <= 0) {
            return false;
        }
        $threshold = $product->get_low_stock_amount();
        if ($threshold === '' || $threshold === null) {
            $threshold = (int) get_option('woocommerce_notify_low_stock_amount', 2);
        }
        return (int) $threshold > 0 && (int) $quantity <= (int) $threshold;
    }
}
