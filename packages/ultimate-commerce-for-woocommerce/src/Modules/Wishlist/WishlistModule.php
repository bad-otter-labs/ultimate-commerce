<?php

namespace BadOtter\UltimateCommerce\Modules\Wishlist;

use BadOtter\UltimateCommerce\Contracts\AbstractModule;
use BadOtter\UltimateCommerce\Contracts\Module;
use BadOtter\UltimateCommerce\Storefront\WishlistControls;
use BadOtter\UltimateCommerce\Wishlist\WishlistBootstrap;
use BadOtter\UltimateCommerce\Wishlist\WishlistRestController;
use BadOtter\UltimateCommerce\Wishlist\WishlistStore;

defined('ABSPATH') || exit;

final class WishlistModule extends AbstractModule
{
    public function key(): string
    {
        return 'wishlist';
    }

    public function name(): string
    {
        return __('Wishlist', 'ultimate-commerce-for-woocommerce');
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
            'frontend' => array(WishlistControls::SCRIPT_HANDLE, WishlistControls::STYLE_HANDLE),
            'admin' => array(),
        );
    }

    public function register(): void
    {
        WishlistStore::hooks();
        WishlistBootstrap::hooks();
        WishlistRestController::hooks();
        WishlistControls::hooks();
        do_action('ultimate_commerce_wishlist_ready', $this);
    }
}
