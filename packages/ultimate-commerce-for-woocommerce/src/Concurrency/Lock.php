<?php

namespace BadOtter\UltimateCommerce\Concurrency;

use BadOtter\UltimateCommerce\Contracts\LockStore;

defined('ABSPATH') || exit;

final class Lock
{
    private static ?LockStore $defaultStore = null;

    public static function store(): LockStore
    {
        if (!self::$defaultStore) {
            self::$defaultStore = new OptionLockStore();
        }
        $store = apply_filters('ultimate_commerce_lock_store', self::$defaultStore);
        return $store instanceof LockStore ? $store : self::$defaultStore;
    }

    /** @return string|false|\WP_Error */
    public static function acquire(string $scope, string $key, int $ttlSeconds = 300)
    {
        return self::store()->acquire($scope, $key, $ttlSeconds);
    }

    public static function release(string $scope, string $key, string $lease): bool
    {
        return self::store()->release($scope, $key, $lease);
    }
}
