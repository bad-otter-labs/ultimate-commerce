<?php

namespace BadOtter\UltimateCommerce\Storefront;

defined('ABSPATH') || exit;

final class Catalog
{
    /** @return array<string, mixed>|\WP_Error */
    public static function query(array $args = array())
    {
        return CatalogQuery::query($args);
    }

    /**
     * @param int|\WC_Product $product
     * @return array<string, mixed>
     */
    public static function product($product): array
    {
        return ProductCardViewModel::fromProduct($product);
    }

    /** @param int|\WC_Product|array<string, mixed> $product */
    public static function renderCard($product, array $context = array()): string
    {
        return ProductCardRenderer::render($product, $context);
    }

    public static function renderList(array $args = array(), array $context = array()): string
    {
        $result = self::query($args);
        if ($result instanceof \WP_Error) {
            return '';
        }

        ob_start();
        do_action('uc_product_list_before', $result, $context);
        echo '<div class="uc-product-list" data-uc-product-list="1">';
        foreach ($result['items'] as $item) {
            echo ProductCardRenderer::render($item, $context); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- renderer escapes its fields.
        }
        echo '</div>';
        do_action('uc_product_list_after', $result, $context);
        return (string) ob_get_clean();
    }

    /** @return array<string, mixed> */
    public static function filterState(array $query): array
    {
        return CatalogFilterRegistry::normalize($query);
    }
}
