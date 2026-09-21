<?php

namespace BadOtter\UltimateCommerce\Modules\RecentlyViewed;

use BadOtter\UltimateCommerce\Contracts\AbstractModule;
use BadOtter\UltimateCommerce\Contracts\Module;
use BadOtter\UltimateCommerce\Storefront\RecentlyViewed;

defined('ABSPATH') || exit;

final class RecentlyViewedModule extends AbstractModule
{
    public function key(): string
    {
        return 'recently_viewed';
    }

    public function name(): string
    {
        return __('Recently Viewed', 'ultimate-commerce-for-woocommerce');
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
            'frontend' => array(RecentlyViewed::SCRIPT_HANDLE, RecentlyViewed::STYLE_HANDLE),
            'admin' => array(),
        );
    }

    public function register(): void
    {
        RecentlyViewed::hooks();
        do_action('ultimate_commerce_recently_viewed_ready', $this);
    }
}
