<?php

namespace BadOtter\UltimateCommerce\Storefront;

defined('ABSPATH') || exit;

final class CatalogFilterRegistry
{
    public const MAX_FILTERS = 16;
    public const MAX_OPTIONS_PER_FILTER = 100;
    public const MAX_SELECTED_PER_FILTER = 12;
    public const DEFAULT_PER_PAGE = 24;
    public const MAX_PER_PAGE = 48;
    public const MAX_PAGE = 500;

    /** @return array<string, array<string, mixed>> */
    public static function definitions(): array
    {
        $definitions = array(
            'price' => array(
                'id' => 'price',
                'label' => __('Price', 'ultimate-commerce-for-woocommerce'),
                'type' => 'price',
            ),
            'availability' => array(
                'id' => 'availability',
                'label' => __('Availability', 'ultimate-commerce-for-woocommerce'),
                'type' => 'availability',
            ),
        );

        if (function_exists('wc_get_attribute_taxonomies')) {
            foreach ((array) wc_get_attribute_taxonomies() as $attribute) {
                $name = isset($attribute->attribute_name) ? sanitize_key((string) $attribute->attribute_name) : '';
                if ($name === '') {
                    continue;
                }
                $taxonomy = wc_attribute_taxonomy_name($name);
                if (!taxonomy_exists($taxonomy) || !is_object_in_taxonomy('product', $taxonomy)) {
                    continue;
                }
                $label = isset($attribute->attribute_label) && $attribute->attribute_label !== ''
                    ? (string) $attribute->attribute_label
                    : $name;
                $definitions[$name] = array(
                    'id' => $name,
                    'label' => $label,
                    'type' => 'taxonomy',
                    'taxonomy' => $taxonomy,
                    'param' => 'filter_' . $name,
                );
            }
        }

        // Store/theme/extension code may choose and reorder from the complete
        // registered attribute set before validateDefinitions() applies the cap.
        $filtered = apply_filters('ultimate_commerce_catalog_filter_definitions', $definitions);
        return self::validateDefinitions(is_array($filtered) ? $filtered : $definitions);
    }

    /** @return array<string, array<string, mixed>> */
    public static function sortDefinitions(): array
    {
        $definitions = array(
            'newest' => array(
                'label' => __('Newest', 'ultimate-commerce-for-woocommerce'),
                'orderby' => 'date',
                'order' => 'desc',
            ),
            'price_asc' => array(
                'label' => __('Price: low to high', 'ultimate-commerce-for-woocommerce'),
                'orderby' => 'price',
                'order' => 'asc',
            ),
            'price_desc' => array(
                'label' => __('Price: high to low', 'ultimate-commerce-for-woocommerce'),
                'orderby' => 'price',
                'order' => 'desc',
            ),
        );
        $filtered = apply_filters('ultimate_commerce_catalog_sort_definitions', $definitions);
        return self::validateSortDefinitions(is_array($filtered) ? $filtered : $definitions);
    }

    /** @return array<string, mixed> */
    public static function normalize(array $input): array
    {
        $definitions = self::definitions();
        $nested = isset($input['filters']) && is_array($input['filters']) ? $input['filters'] : array();
        $selected = array();

        foreach ($definitions as $id => $definition) {
            if (($definition['type'] ?? '') !== 'taxonomy') {
                continue;
            }
            $param = (string) ($definition['param'] ?? ('filter_' . $id));
            $raw = array_key_exists($param, $input) ? $input[$param] : ($nested[$id] ?? array());
            $values = self::termValues($raw);
            if ($values !== array()) {
                $selected[$id] = $values;
            }
        }

        $priceInput = isset($nested['price']) && is_array($nested['price']) ? $nested['price'] : array();
        $minPrice = self::price($input['min_price'] ?? ($priceInput['min'] ?? null));
        $maxPrice = self::price($input['max_price'] ?? ($priceInput['max'] ?? null));
        if ($minPrice !== null && $maxPrice !== null && (float) $minPrice > (float) $maxPrice) {
            [$minPrice, $maxPrice] = [$maxPrice, $minPrice];
        }

        $availabilityRaw = $input['availability'] ?? ($nested['availability'] ?? array());
        $availability = array_values(array_intersect(
            self::termValues($availabilityRaw),
            array('in_stock', 'out_of_stock', 'on_backorder')
        ));

        $sorts = self::sortDefinitions();
        $sort = sanitize_key((string) ($input['sort'] ?? 'newest'));
        if (!isset($sorts[$sort])) {
            $sort = 'newest';
        }

        $state = self::validateState(array(
            'page' => $input['page'] ?? 1,
            'per_page' => $input['per_page'] ?? self::DEFAULT_PER_PAGE,
            'sort' => $sort,
            'filters' => $selected,
            'price' => array('min' => $minPrice, 'max' => $maxPrice),
            'availability' => $availability,
        ), $definitions, $sorts);

        $filtered = apply_filters('ultimate_commerce_catalog_filter_state', $state, $input, $definitions);
        return self::validateState(is_array($filtered) ? $filtered : $state, $definitions, $sorts);
    }

    /** @return array<int, array<string, mixed>> */
    public static function describe(array $state): array
    {
        $descriptors = array();
        foreach (self::definitions() as $id => $definition) {
            $type = (string) ($definition['type'] ?? '');
            $descriptor = $definition;
            if ($type === 'taxonomy') {
                $selected = (array) ($state['filters'][$id] ?? array());
                $terms = get_terms(array(
                    'taxonomy' => (string) $definition['taxonomy'],
                    'hide_empty' => true,
                    'number' => self::MAX_OPTIONS_PER_FILTER,
                    'orderby' => 'name',
                    'order' => 'ASC',
                ));
                $options = array();
                if (!is_wp_error($terms)) {
                    foreach ((array) $terms as $term) {
                        $options[] = array(
                            'value' => (string) $term->slug,
                            'label' => (string) $term->name,
                            'count' => (int) $term->count,
                            'selected' => in_array((string) $term->slug, $selected, true),
                        );
                    }
                }
                $descriptor['selected'] = $selected;
                $descriptor['options'] = $options;
            } elseif ($type === 'price') {
                $descriptor['selected'] = (array) ($state['price'] ?? array('min' => null, 'max' => null));
            } elseif ($type === 'availability') {
                $selected = (array) ($state['availability'] ?? array());
                $labels = array(
                    'in_stock' => __('In stock', 'ultimate-commerce-for-woocommerce'),
                    'out_of_stock' => __('Out of stock', 'ultimate-commerce-for-woocommerce'),
                    'on_backorder' => __('On backorder', 'ultimate-commerce-for-woocommerce'),
                );
                $descriptor['selected'] = $selected;
                $descriptor['options'] = array_map(
                    static fn(string $value, string $label): array => array(
                        'value' => $value,
                        'label' => $label,
                        'count' => null,
                        'selected' => in_array($value, $selected, true),
                    ),
                    array_keys($labels),
                    array_values($labels)
                );
            }
            $descriptors[] = $descriptor;
        }

        $filtered = apply_filters('ultimate_commerce_catalog_filter_descriptors', $descriptors, $state);
        return is_array($filtered) ? $filtered : $descriptors;
    }

    /** @return array<string, string> */
    public static function canonicalQuery(array $state): array
    {
        $query = array();
        $definitions = self::definitions();
        foreach ((array) ($state['filters'] ?? array()) as $id => $values) {
            if (!isset($definitions[$id]) || ($definitions[$id]['type'] ?? '') !== 'taxonomy') {
                continue;
            }
            $values = array_values(array_unique(array_map('sanitize_title', (array) $values)));
            sort($values, SORT_STRING);
            if ($values !== array()) {
                $query[(string) $definitions[$id]['param']] = implode(',', $values);
            }
        }
        if (($state['price']['min'] ?? null) !== null) {
            $query['min_price'] = (string) $state['price']['min'];
        }
        if (($state['price']['max'] ?? null) !== null) {
            $query['max_price'] = (string) $state['price']['max'];
        }
        if (!empty($state['availability'])) {
            $values = array_values((array) $state['availability']);
            sort($values, SORT_STRING);
            $query['availability'] = implode(',', $values);
        }
        if (($state['sort'] ?? 'newest') !== 'newest') {
            $query['sort'] = sanitize_key((string) $state['sort']);
        }
        if ((int) ($state['page'] ?? 1) > 1) {
            $query['page'] = (string) (int) $state['page'];
        }
        if ((int) ($state['per_page'] ?? self::DEFAULT_PER_PAGE) !== self::DEFAULT_PER_PAGE) {
            $query['per_page'] = (string) (int) $state['per_page'];
        }
        ksort($query, SORT_STRING);
        return $query;
    }

    /** @return array<string, mixed>|null */
    public static function definition(string $id): ?array
    {
        $definitions = self::definitions();
        return $definitions[$id] ?? null;
    }

    /** @return array<string, mixed> */
    public static function sortDefinition(string $id): array
    {
        $sorts = self::sortDefinitions();
        return $sorts[$id] ?? $sorts['newest'];
    }

    /** @param mixed $raw @return array<int, string> */
    private static function termValues($raw): array
    {
        $values = is_array($raw) ? $raw : explode(',', (string) $raw);
        $clean = array();
        foreach ($values as $value) {
            if (!is_scalar($value)) {
                continue;
            }
            $value = sanitize_title((string) $value);
            if ($value !== '') {
                $clean[] = $value;
            }
            if (count($clean) >= self::MAX_SELECTED_PER_FILTER) {
                break;
            }
        }
        return array_values(array_unique($clean));
    }

    /** @param mixed $raw */
    private static function price($raw): ?string
    {
        if ($raw === null || $raw === '') {
            return null;
        }
        $value = wc_format_decimal($raw, wc_get_price_decimals());
        if ($value === '' || !is_numeric($value) || (float) $value < 0) {
            return null;
        }
        return (string) $value;
    }

    /** @param mixed $raw */
    private static function boundedInt($raw, int $min, int $max): int
    {
        $value = is_numeric($raw) ? (int) $raw : $min;
        return max($min, min($max, $value));
    }

    /**
     * @param array<string, mixed> $state
     * @param array<string, array<string, mixed>> $definitions
     * @param array<string, array<string, mixed>> $sorts
     * @return array<string, mixed>
     */
    private static function validateState(array $state, array $definitions, array $sorts): array
    {
        $validated = $state;
        $validated['page'] = self::boundedInt($state['page'] ?? 1, 1, self::MAX_PAGE);
        $validated['per_page'] = self::boundedInt($state['per_page'] ?? self::DEFAULT_PER_PAGE, 1, self::MAX_PER_PAGE);

        $sort = sanitize_key((string) ($state['sort'] ?? 'newest'));
        $validated['sort'] = isset($sorts[$sort]) ? $sort : 'newest';

        $filters = array();
        foreach ((array) ($state['filters'] ?? array()) as $id => $values) {
            $id = sanitize_key((string) $id);
            if (!isset($definitions[$id]) || ($definitions[$id]['type'] ?? '') !== 'taxonomy') {
                continue;
            }
            $clean = self::termValues($values);
            if ($clean !== array()) {
                $filters[$id] = $clean;
            }
        }
        $validated['filters'] = $filters;

        $priceState = isset($state['price']) && is_array($state['price']) ? $state['price'] : array();
        $minPrice = self::price($priceState['min'] ?? null);
        $maxPrice = self::price($priceState['max'] ?? null);
        if ($minPrice !== null && $maxPrice !== null && (float) $minPrice > (float) $maxPrice) {
            [$minPrice, $maxPrice] = [$maxPrice, $minPrice];
        }
        $validated['price'] = array('min' => $minPrice, 'max' => $maxPrice);

        $validated['availability'] = array_values(array_intersect(
            self::termValues($state['availability'] ?? array()),
            array('in_stock', 'out_of_stock', 'on_backorder')
        ));

        $selectedCount = count($validated['availability']);
        foreach ($filters as $values) {
            $selectedCount += count($values);
        }
        if ($minPrice !== null) {
            ++$selectedCount;
        }
        if ($maxPrice !== null) {
            ++$selectedCount;
        }
        $validated['selected_count'] = $selectedCount;

        return $validated;
    }

    /** @param array<string, mixed> $definitions @return array<string, array<string, mixed>> */
    private static function validateDefinitions(array $definitions): array
    {
        $valid = array();
        foreach (array_slice($definitions, 0, self::MAX_FILTERS, true) as $key => $definition) {
            if (!is_array($definition)) {
                continue;
            }
            $id = sanitize_key((string) ($definition['id'] ?? $key));
            $type = sanitize_key((string) ($definition['type'] ?? ''));
            if ($id === '' || !in_array($type, array('taxonomy', 'price', 'availability'), true)) {
                continue;
            }
            if ($type === 'taxonomy') {
                $taxonomy = sanitize_key((string) ($definition['taxonomy'] ?? ''));
                if ($taxonomy === '' || !taxonomy_exists($taxonomy) || !is_object_in_taxonomy('product', $taxonomy)) {
                    continue;
                }
                $definition['taxonomy'] = $taxonomy;
                $definition['param'] = sanitize_key((string) ($definition['param'] ?? ('filter_' . $id)));
            }
            $definition['id'] = $id;
            $definition['type'] = $type;
            $definition['label'] = (string) ($definition['label'] ?? $id);
            $valid[$id] = $definition;
        }
        return $valid;
    }

    /** @param array<string, mixed> $definitions @return array<string, array<string, mixed>> */
    private static function validateSortDefinitions(array $definitions): array
    {
        $valid = array();
        foreach (array_slice($definitions, 0, 16, true) as $key => $definition) {
            if (!is_array($definition)) {
                continue;
            }
            $id = sanitize_key((string) $key);
            $orderby = sanitize_key((string) ($definition['orderby'] ?? 'date'));
            $order = strtolower((string) ($definition['order'] ?? 'desc'));
            if ($id === '' || $orderby === '' || !in_array($order, array('asc', 'desc'), true)) {
                continue;
            }
            $definition['label'] = (string) ($definition['label'] ?? $id);
            $definition['orderby'] = $orderby;
            $definition['order'] = $order;
            $valid[$id] = $definition;
        }
        if (!isset($valid['newest'])) {
            $valid['newest'] = array(
                'label' => __('Newest', 'ultimate-commerce-for-woocommerce'),
                'orderby' => 'date',
                'order' => 'desc',
            );
        }
        return $valid;
    }
}
