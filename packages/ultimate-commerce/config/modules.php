<?php

use BadOtter\UltimateCommerce\Modules\Account\AccountModule;
use BadOtter\UltimateCommerce\Modules\Cart\CartModule;
use BadOtter\UltimateCommerce\Modules\ProductDisplay\ProductDisplayModule;
use BadOtter\UltimateCommerce\Modules\Returns\ReturnsModule;
use BadOtter\UltimateCommerce\Modules\Variations\VariationsModule;

return array(
    ProductDisplayModule::class,
    VariationsModule::class,
    CartModule::class,
    AccountModule::class,
    ReturnsModule::class,
);
