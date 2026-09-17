<?php

namespace BadOtter\UltimateCommerce\Storefront;

defined('ABSPATH') || exit;

final class ProductCardViewModel
{
    /**
     * @param int|\WC_Product $product
     * @return array<string, mixed>
     */
    public static function fromProduct($product, array $context = array()): array
    {
        $product = is_numeric($product) ? wc_get_product((int) $product) : $product;
        if (!$product instanceof \WC_Product) {
            return array();
        }

        $primaryId = (int) $product->get_image_id();
        $galleryIds = array_values(array_filter(array_map('absint', (array) $product->get_gallery_image_ids())));
        $secondaryId = 0;
        foreach ($galleryIds as $galleryId) {
            if ($galleryId !== $primaryId) {
                $secondaryId = $galleryId;
                break;
            }
        }

        $variationState = $product instanceof \WC_Product_Variable
            ? VariationViewModel::forProduct($product, array(), (array) ($context['variation_context'] ?? array()))
            : array('variable' => false, 'attributes' => array(), 'variations' => array(), 'variation_ids' => array(), 'action' => array());

        $action = $product instanceof \WC_Product_Variable
            ? (array) ($variationState['action'] ?? array())
            : self::simpleAction($product);

        $ratingCount = (int) $product->get_rating_count();
        $brand = self::brand($product);
        $classes = array('uc-product-card', 'uc-product-card--' . sanitize_html_class($product->get_type()));
        if ($product->is_on_sale()) {
            $classes[] = 'uc-product-card--on-sale';
        }
        if (!$product->is_in_stock()) {
            $classes[] = 'uc-product-card--out-of-stock';
        }
        $classes = apply_filters('uc_product_card_classes', $classes, $product);
        $classes = array_values(array_unique(array_filter(array_map('sanitize_html_class', is_array($classes) ? $classes : array()))));

        $data = array(
            'schema' => 'uc.product-card.v1',
            'id' => $product->get_id(),
            'type' => $product->get_type(),
            'name' => $product->get_name(),
            'slug' => $product->get_slug(),
            'url' => $product->get_permalink(),
            'sku' => $product->get_sku(),
            'brand' => $brand,
            'media' => array(
                'primary' => MediaViewModel::fromAttachment($primaryId),
                'secondary' => MediaViewModel::fromAttachment($secondaryId),
            ),
            'price' => array(
                'current' => (string) $product->get_price(),
                'regular' => (string) $product->get_regular_price(),
                'sale' => (string) $product->get_sale_price(),
                'html' => $product->get_price_html(),
                'on_sale' => $product->is_on_sale(),
                'currency' => function_exists('get_woocommerce_currency') ? get_woocommerce_currency() : '',
            ),
            'rating' => $ratingCount > 0 ? array(
                'average' => (float) $product->get_average_rating(),
                'count' => $ratingCount,
            ) : null,
            'availability' => array(
                'in_stock' => $product->is_in_stock(),
                'purchasable' => $product->is_purchasable(),
                'stock_status' => $product->get_stock_status(),
                'stock_quantity' => $product->get_stock_quantity(),
                'low_stock' => self::isLowStock($product),
            ),
            'variation' => $variationState,
            'action' => $action,
            'classes' => $classes,
            'slots' => array('before_media', 'after_media', 'before_title', 'after_title', 'after_price', 'before_action', 'after_action'),
            'cache' => array('contains_cart_state' => false, 'contains_nonce' => false, 'contains_customer_identity' => false),
        );

        // Compatibility aliases for the historical UC product-view-model contract.
        $data['permalink'] = $data['url'];
        $data['price_html'] = $data['price']['html'];
        $data['regular_price'] = $data['price']['regular'];
        $data['sale_price'] = $data['price']['sale'];
        $data['on_sale'] = $data['price']['on_sale'];
        $data['purchasable'] = $data['availability']['purchasable'];
        $data['in_stock'] = $data['availability']['in_stock'];
        $data['stock_status'] = $data['availability']['stock_status'];
        $data['stock_quantity'] = $data['availability']['stock_quantity'];
        $data['low_stock_amount'] = $product->get_low_stock_amount();
        $data['image_id'] = $primaryId;
        $data['gallery_image_ids'] = $galleryIds;
        $data['attributes'] = $variationState['attributes'] ?? array();
        $data['variation_ids'] = $variationState['variation_ids'] ?? array();

        $data = apply_filters('uc_product_card_view_model', $data, $product);
        $data = is_array($data) ? $data : array();
        $legacy = apply_filters('uc_product_view_model', $data, $product);
        return is_array($legacy) ? $legacy : $data;
    }

    /** @return array<string, mixed>|null */
    private static function brand(\WC_Product $product): ?array
    {
        if (!taxonomy_exists('product_brand')) {
            return null;
        }
        $terms = get_the_terms($product->get_id(), 'product_brand');
        if (is_wp_error($terms) || empty($terms)) {
            return null;
        }
        $term = reset($terms);
        $url = get_term_link($term);
        return array(
            'id' => (int) $term->term_id,
            'name' => (string) $term->name,
            'slug' => (string) $term->slug,
            'url' => is_wp_error($url) ? '' : (string) $url,
        );
    }

    /** @return array<string, mixed> */
    private static function simpleAction(\WC_Product $product): array
    {
        $enabled = $product->is_purchasable() && $product->is_in_stock();
        return array(
            'type' => $enabled ? 'add_to_cart' : 'view_product',
            'enabled' => $enabled,
            'label' => $enabled ? $product->add_to_cart_text() : __('View product', 'ultimate-commerce-for-woocommerce'),
            'aria_label' => $product->add_to_cart_description(),
            'url' => $enabled ? $product->add_to_cart_url() : $product->get_permalink(),
            'product_id' => $product->get_id(),
            'variation_id' => 0,
            'store_api' => $enabled ? array(
                'route' => '/wc/store/v1/cart/add-item',
                'id' => $product->get_id(),
                'quantity' => 1,
            ) : null,
            'stock_truth' => 'woocommerce',
        );
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
