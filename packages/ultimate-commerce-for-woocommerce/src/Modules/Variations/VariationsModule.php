<?php

namespace BadOtter\UltimateCommerce\Modules\Variations;

use BadOtter\UltimateCommerce\Contracts\Module;

defined('ABSPATH') || exit;

final class VariationsModule implements Module
{
    public function key(): string
    {
        return 'variations';
    }

    public function register(): void
    {
        do_action('uc_variations_ready', $this);
    }

    public function state(\WC_Product_Variation $variation): array
    {
        $state = array(
            'id' => $variation->get_id(),
            'attributes' => $variation->get_variation_attributes(false),
            'purchasable' => $variation->is_purchasable(),
            'in_stock' => $variation->is_in_stock(),
            'stock_status' => $variation->get_stock_status(),
            'stock_quantity' => $variation->get_stock_quantity(),
            'price_html' => $variation->get_price_html(),
            'image_id' => $variation->get_image_id(),
        );

        return (array) apply_filters('uc_variation_view_model', $state, $variation);
    }
}
