<?php

namespace BadOtter\UltimateCommerce\Modules\ProductDisplay;

use BadOtter\UltimateCommerce\Contracts\AbstractModule;
use BadOtter\UltimateCommerce\Contracts\Module;

defined('ABSPATH') || exit;

final class ProductDisplayModule extends AbstractModule
{
    public function key(): string
    {
        return 'product_display';
    }

    public function name(): string
    {
        return __('Product Display', 'ultimate-commerce-for-woocommerce');
    }

    public function product(): string
    {
        return 'ultimate-commerce-for-woocommerce';
    }

    public function tier(): string
    {
        return Module::TIER_FREE;
    }

    public function register(): void
    {
        do_action('ultimate_commerce_product_display_ready', $this);
    }

    /** @param int|\WC_Product $product */
    public function viewModel($product): array
    {
        return ProductViewModel::fromProduct($product);
    }
}
