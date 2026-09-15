<?php

namespace BadOtter\UltimateCommerce\Modules\ProductDisplay;

defined('ABSPATH') || exit;

final class ProductViewModel
{
    /**
     * Build a presentation-neutral product payload using WooCommerce as the source of truth.
     *
     * @param int|\WC_Product $product
     */
    public static function fromProduct($product): array
    {
        $product = is_numeric($product) ? wc_get_product((int) $product) : $product;
        if (!$product instanceof \WC_Product) {
            return array();
        }

        $data = array(
            'id' => $product->get_id(),
            'type' => $product->get_type(),
            'name' => $product->get_name(),
            'slug' => $product->get_slug(),
            'permalink' => $product->get_permalink(),
            'sku' => $product->get_sku(),
            'price_html' => $product->get_price_html(),
            'regular_price' => $product->get_regular_price(),
            'sale_price' => $product->get_sale_price(),
            'on_sale' => $product->is_on_sale(),
            'purchasable' => $product->is_purchasable(),
            'in_stock' => $product->is_in_stock(),
            'stock_status' => $product->get_stock_status(),
            'stock_quantity' => $product->get_stock_quantity(),
            'low_stock_amount' => $product->get_low_stock_amount(),
            'image_id' => $product->get_image_id(),
            'gallery_image_ids' => $product->get_gallery_image_ids(),
            'attributes' => $product->get_attributes(),
            'variation_ids' => $product instanceof \WC_Product_Variable ? $product->get_children() : array(),
        );

        /**
         * Filter the generic Ultimate Commerce product payload.
         *
         * Store-specific plugins may add presentation hints, but must not replace Woo-owned truth.
         */
        return (array) apply_filters('uc_product_view_model', $data, $product);
    }
}
