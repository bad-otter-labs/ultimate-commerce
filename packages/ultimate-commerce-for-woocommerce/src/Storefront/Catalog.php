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
        do_action('ultimate_commerce_product_list_before', $result, $context);
        echo '<div class="uc-product-list" data-uc-product-list="1">';
        $items = (array) ($result['items'] ?? array());
        if ($items === array()) {
            $state = (array) ($result['state'] ?? array());
            $search = (string) ($state['search'] ?? '');
            if ($search !== '') {
                $message = sprintf(
                    /* translators: %s: The shopper's search term. */
                    __('No products found for "%s". Try another search or change your filters.', 'bad-otter-ultimate-commerce-woocommerce'),
                    $search
                );
            } elseif (!empty($state['selected_count'])) {
                $message = __('No products match these filters. Try removing a filter.', 'bad-otter-ultimate-commerce-woocommerce');
            } else {
                $message = __('No products are available to display.', 'bad-otter-ultimate-commerce-woocommerce');
            }
            echo '<p class="uc-product-list__empty" data-uc-catalog-empty="1" role="status">' . esc_html($message) . '</p>';
        } else {
            foreach ($items as $item) {
                echo ProductCardRenderer::render($item, $context); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- renderer escapes its fields.
            }
        }
        echo '</div>';
        do_action('ultimate_commerce_product_list_after', $result, $context);
        return (string) ob_get_clean();
    }

    /** @return array<string, mixed> */
    public static function filterState(array $query): array
    {
        return CatalogFilterRegistry::normalize($query);
    }
}
