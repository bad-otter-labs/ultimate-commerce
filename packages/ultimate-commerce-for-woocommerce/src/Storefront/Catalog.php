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

            // Offer an explicit recovery path only for a search/filter miss.
            // WordPress validates the shop URL against the store's allowed hosts;
            // no user-provided redirect or query parameter is ever accepted.
            if ($search !== '' || !empty($state['selected_count'])) {
                $shopUrl = function_exists('wc_get_page_permalink')
                    ? wc_get_page_permalink('shop')
                    : '';
                $shopUrl = is_string($shopUrl) && function_exists('wp_validate_redirect')
                    ? wp_validate_redirect($shopUrl, '')
                    : '';
                if ($shopUrl !== '' && function_exists('esc_url')) {
                    echo '<a class="uc-product-list__recovery" data-uc-catalog-recovery="1" href="' . esc_url($shopUrl) . '">'
                        . esc_html(__('Browse all products', 'bad-otter-ultimate-commerce-woocommerce'))
                        . '</a>';
                }
            }
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
