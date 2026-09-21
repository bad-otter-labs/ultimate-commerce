<?php

declare(strict_types=1);

define('ABSPATH', __DIR__ . '/');

function __($text, $domain = null): string { return (string) $text; }
function sanitize_key($value): string { return strtolower((string) preg_replace('/[^a-z0-9_\-]/i', '', (string) $value)); }
function sanitize_title($value): string { return trim(strtolower((string) preg_replace('/[^a-z0-9_\-]+/i', '-', (string) $value)), '-'); }
function wc_format_decimal($value, $decimals = 2): string { return number_format((float) $value, (int) $decimals, '.', ''); }
function wc_get_price_decimals(): int { return 2; }
function wc_attribute_taxonomy_name($name): string { return 'pa_' . sanitize_key($name); }
function wc_get_attribute_taxonomies(): array {
    return array((object) array('attribute_name' => 'colour', 'attribute_label' => 'Colour'));
}
function taxonomy_exists($taxonomy): bool { return $taxonomy === 'pa_colour'; }
function is_object_in_taxonomy($object, $taxonomy): bool { return $object === 'product' && $taxonomy === 'pa_colour'; }

function apply_filters($tag, $value, ...$args)
{
    if ($tag !== 'ultimate_commerce_catalog_filter_state') {
        return $value;
    }

    $value['page'] = 999999;
    $value['per_page'] = 999999;
    $value['sort'] = 'not-a-real-sort';
    $value['filters']['colour'] = array_map(static fn(int $index): string => 'shade-' . $index, range(1, 20));
    $value['filters']['unknown'] = array('x');
    $value['price'] = array('min' => '250', 'max' => '50');
    $value['availability'] = array('in_stock', 'made_up');
    $value['selected_count'] = 999999;
    return $value;
}

require_once __DIR__ . '/../packages/ultimate-commerce-for-woocommerce/src/Storefront/CatalogFilterRegistry.php';

use BadOtter\UltimateCommerce\Storefront\CatalogFilterRegistry;

function uc_storefront_extension_assert(bool $condition, string $message): void
{
    if (!$condition) {
        fwrite(STDERR, "FAIL: {$message}\n");
        exit(1);
    }
}

$state = CatalogFilterRegistry::normalize(array('filter_colour' => 'olive'));
uc_storefront_extension_assert($state['page'] === CatalogFilterRegistry::MAX_PAGE, 'extension-modified page must be re-bounded');
uc_storefront_extension_assert($state['per_page'] === CatalogFilterRegistry::MAX_PER_PAGE, 'extension-modified per_page must be re-bounded');
uc_storefront_extension_assert($state['sort'] === 'newest', 'unknown extension sort must fail back to newest');
uc_storefront_extension_assert(count($state['filters']['colour']) === CatalogFilterRegistry::MAX_SELECTED_PER_FILTER, 'extension filter selections must remain capped');
uc_storefront_extension_assert(!isset($state['filters']['unknown']), 'extension may not inject undefined taxonomy filters');
uc_storefront_extension_assert($state['price']['min'] === '50.00' && $state['price']['max'] === '250.00', 'extension price range must be normalized and ordered');
uc_storefront_extension_assert($state['availability'] === array('in_stock'), 'extension availability must be revalidated');
uc_storefront_extension_assert($state['selected_count'] === 15, 'selected count must be recomputed from validated state');

fwrite(STDOUT, "Storefront extension-boundary tests passed\n");
