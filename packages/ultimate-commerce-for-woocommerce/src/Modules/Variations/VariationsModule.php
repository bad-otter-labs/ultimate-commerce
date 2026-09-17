<?php

namespace BadOtter\UltimateCommerce\Modules\Variations;

use BadOtter\UltimateCommerce\Contracts\AbstractModule;
use BadOtter\UltimateCommerce\Contracts\Module;
use BadOtter\UltimateCommerce\Storefront\VariationViewModel;

defined('ABSPATH') || exit;

final class VariationsModule extends AbstractModule
{
    public function key(): string
    {
        return 'variations';
    }

    public function name(): string
    {
        return __('Variants & Swatches', 'ultimate-commerce-for-woocommerce');
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
        do_action('uc_variations_ready', $this);
    }

    /** Historical compatibility facade for `uc_variation_view_model`. */
    public function state(\WC_Product_Variation $variation): array
    {
        return VariationViewModel::fromVariation($variation);
    }
}
