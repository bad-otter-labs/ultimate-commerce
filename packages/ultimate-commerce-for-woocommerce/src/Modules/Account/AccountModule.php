<?php

namespace BadOtter\UltimateCommerce\Modules\Account;

use BadOtter\UltimateCommerce\Contracts\Module;

defined('ABSPATH') || exit;

final class AccountModule implements Module
{
    public function key(): string
    {
        return 'account';
    }

    public function register(): void
    {
        do_action('uc_account_ready', $this);
    }
}
