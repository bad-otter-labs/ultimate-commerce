<?php

namespace BadOtter\UltimateCommerce\Modules\Account;

use BadOtter\UltimateCommerce\Contracts\AbstractModule;
use BadOtter\UltimateCommerce\Contracts\Module;

defined('ABSPATH') || exit;

final class AccountModule extends AbstractModule
{
    public function key(): string
    {
        return 'account';
    }

    public function name(): string
    {
        return __('Account', 'ultimate-commerce-for-woocommerce');
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
        do_action('uc_account_ready', $this);
    }
}
