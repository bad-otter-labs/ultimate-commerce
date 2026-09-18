<?php

defined('ABSPATH') || exit;

add_shortcode(
    'uc_accessibility_fixture',
    static function (array $atts = array()): string {
        $atts = shortcode_atts(array('product_id' => 0), $atts, 'uc_accessibility_fixture');
        $productId = absint($atts['product_id']);
        $recentProductId = absint(get_option('uc_a11y_recent_product_id', 0));

        if (
            $productId <= 0
            || !class_exists(\BadOtter\UltimateCommerce\Storefront\CartDrawer::class)
            || !class_exists(\BadOtter\UltimateCommerce\Storefront\ProductCardRenderer::class)
        ) {
            return '';
        }

        $trigger = \BadOtter\UltimateCommerce\Storefront\CartDrawer::trigger(
            array(
                'label' => 'Open cart',
                'class' => 'uc-a11y-cart-trigger',
            )
        );
        $card = \BadOtter\UltimateCommerce\Storefront\ProductCardRenderer::render(
            $productId,
            array('accessibility_fixture' => true)
        );

        return sprintf(
            '<div data-uc-a11y-fixture="1" data-recent-product-id="%1$d">%2$s%3$s</div>',
            $recentProductId,
            $trigger,
            $card
        );
    }
);
