<?php

namespace BadOtter\UltimateCommerce\Modules\ProductDisplay;

use BadOtter\UltimateCommerce\Contracts\Module;

defined('ABSPATH') || exit;

final class ProductDisplayModule implements Module
{
    public function key(): string
    {
        return 'product_display';
    }

    public function register(): void
    {
        do_action('uc_product_display_ready', $this);
    }

    /** @param int|\WC_Product $product */
    public function viewModel($product): array
    {
        return ProductViewModel::fromProduct($product);
    }
}
