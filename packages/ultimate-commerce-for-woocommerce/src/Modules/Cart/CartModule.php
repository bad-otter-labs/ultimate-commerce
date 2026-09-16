<?php

namespace BadOtter\UltimateCommerce\Modules\Cart;

use BadOtter\UltimateCommerce\Contracts\Module;

defined('ABSPATH') || exit;

final class CartModule implements Module
{
    public function key(): string
    {
        return 'cart';
    }

    public function register(): void
    {
        do_action('uc_cart_ready', $this);
    }
}
