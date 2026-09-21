<?php

namespace BadOtter\UltimateCommerce\Modules\Cart;

use BadOtter\UltimateCommerce\Contracts\AbstractModule;
use BadOtter\UltimateCommerce\Contracts\Module;
use BadOtter\UltimateCommerce\Storefront\CartDrawer;

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

    /** @return array<string, list<string>> */
    public function assets(): array
    {
        return array(
            'frontend' => array(CartDrawer::SCRIPT_HANDLE, CartDrawer::STYLE_HANDLE),
            'admin' => array(),
        );
    }

    public function register(): void
    {
        CartDrawer::hooks();
        do_action('ultimate_commerce_cart_ready', $this);
    }
}
