<?php

namespace BadOtter\UltimateCommerce\Compatibility;

defined('ABSPATH') || exit;

final class WooCommerceCompatibility
{
    public static function hooks(): void
    {
        add_action('before_woocommerce_init', array(__CLASS__, 'declare'));
    }

    public static function declare(): void
    {
        if (!class_exists(\Automattic\WooCommerce\Utilities\FeaturesUtil::class)) {
            return;
        }

        \Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility(
            'custom_order_tables',
            ULTIMATE_COMMERCE_FILE,
            true
        );

        \Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility(
            'cart_checkout_blocks',
            ULTIMATE_COMMERCE_FILE,
            true
        );
    }
}
