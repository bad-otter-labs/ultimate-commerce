<?php

defined('ABSPATH') || exit;

use BadOtter\UltimateCommerce\Modules\Account\AccountModule;
use BadOtter\UltimateCommerce\Modules\Cart\CartModule;
use BadOtter\UltimateCommerce\Modules\ProductDisplay\ProductDisplayModule;
use BadOtter\UltimateCommerce\Modules\Variations\VariationsModule;

return array(
    ProductDisplayModule::class,
    VariationsModule::class,
    CartModule::class,
    WishlistModule::class,
    AccountModule::class,
);
