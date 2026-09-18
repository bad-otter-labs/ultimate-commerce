<?php

declare(strict_types=1);

$root = dirname(__DIR__);
$package = $root . '/packages/ultimate-commerce-for-woocommerce';

function uc_stock_read(string $path): string
{
    $contents = file_get_contents($path);
    if (!is_string($contents)) {
        throw new RuntimeException('Unable to read ' . $path);
    }
    return $contents;
}

function uc_stock_assert_contains(string $needle, string $haystack, string $message): void
{
    if (!str_contains($haystack, $needle)) {
        throw new RuntimeException($message . ' Missing: ' . $needle);
    }
}

function uc_stock_assert_not_contains(string $needle, string $haystack, string $message): void
{
    if (str_contains($haystack, $needle)) {
        throw new RuntimeException($message . ' Forbidden: ' . $needle);
    }
}

$product = uc_stock_read($package . '/src/Storefront/ProductCardViewModel.php');
$variation = uc_stock_read($package . '/src/Storefront/VariationViewModel.php');
$renderer = uc_stock_read($package . '/src/Storefront/ProductCardRenderer.php');
$controller = uc_stock_read($package . '/assets/js/variation-controls.js');
$docs = uc_stock_read($root . '/docs/storefront-catalogue-contract-v1.md');

uc_stock_assert_contains("'stock_html' => self::stockHtml(\$product)", $product, 'Product-card state must expose Woo stock HTML.');
uc_stock_assert_contains("'stock_html' => self::stockHtml(\$variation)", $variation, 'Variation state must expose Woo stock HTML.');
uc_stock_assert_contains("wc_get_stock_html(\$product)", $product, 'Product stock presentation must come from WooCommerce.');
uc_stock_assert_contains("wc_get_stock_html(\$product)", $variation, 'Variation stock presentation must come from WooCommerce.');
uc_stock_assert_contains("wp_kses_post(\$html)", $product, 'Product stock HTML must be sanitized before entering public state.');
uc_stock_assert_contains("wp_kses_post(\$html)", $variation, 'Variation stock HTML must be sanitized before entering public state.');
uc_stock_assert_contains('return self::sanitizeStockState(', $product, 'Product stock HTML must be re-sanitized after public view-model filters.');
uc_stock_assert_contains('return self::sanitizeStockState(', $variation, 'Variation stock HTML must be re-sanitized after public variation filters.');
uc_stock_assert_contains('data-uc-stock="1"', $renderer, 'Default product cards must expose the semantic stock region.');
uc_stock_assert_contains('aria-live="polite"', $renderer, 'Stock region must announce variation-driven stock-copy changes.');
uc_stock_assert_contains("function updateStock( stock, variation )", $controller, 'Variation controller must own stock-region presentation updates.');
uc_stock_assert_contains("variation.stock_html", $controller, 'Variation controller must consume Woo-generated stock HTML from variation state.');
uc_stock_assert_not_contains("variation.stock_html !== ''", $controller, 'An intentionally empty Woo variation stock message must clear the region rather than restore parent stock copy.');
uc_stock_assert_contains('querySelector( \'[data-uc-stock="1"]\' )', $controller, 'Variation controller must target the semantic stock region.');
uc_stock_assert_contains("updateStock( stock, variation );", $controller, 'Variation UI refresh must update stock presentation.');
uc_stock_assert_contains('wc_get_stock_html()', $docs, 'Storefront contract must document Woo-owned stock presentation.');
uc_stock_assert_contains('availability.stock_html', $docs, 'Storefront contract must document product stock HTML state.');

foreach (array($product, $variation, $renderer, $controller) as $source) {
    foreach (array('set_stock_quantity', 'set_stock_status', 'wc_update_product_stock', 'wc_update_product_stock_status', 'update_post_meta(') as $forbidden) {
        uc_stock_assert_not_contains($forbidden, $source, 'Free stock presentation must not mutate or persist inventory.');
    }
}

echo "Ultimate Commerce stock presentation contract passed.\n";
