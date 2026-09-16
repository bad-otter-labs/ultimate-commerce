<?php

namespace BadOtter\UltimateCommerce\Modules\Cart;

use BadOtter\UltimateCommerce\Contracts\AbstractModule;
use BadOtter\UltimateCommerce\Contracts\Module;

defined('ABSPATH') || exit;

final class CartModule extends AbstractModule
{
    public function key(): string
    {
        return 'cart';
    }

    public function name(): string
    {
        return __('Cart', 'ultimate-commerce-for-woocommerce');
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
        do_action('uc_cart_ready', $this);
    }
}
