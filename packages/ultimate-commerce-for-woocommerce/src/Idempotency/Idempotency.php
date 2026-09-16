<?php

namespace BadOtter\UltimateCommerce\Idempotency;

use BadOtter\UltimateCommerce\Contracts\IdempotencyStore;

defined('ABSPATH') || exit;

final class Idempotency
{
    private static ?IdempotencyStore $defaultStore = null;

    public static function store(): IdempotencyStore
    {
        if (!self::$defaultStore) {
            self::$defaultStore = new OptionIdempotencyStore();
        }
        $store = apply_filters('uc_idempotency_store', self::$defaultStore);
        return $store instanceof IdempotencyStore ? $store : self::$defaultStore;
    }

    public static function claim(string $scope, string $key, int $ttlSeconds = 86400)
    {
        return self::store()->claim($scope, $key, $ttlSeconds);
    }

    public static function complete(string $scope, string $key, string $lease, string $resultRef = ''): bool
    {
        return self::store()->complete($scope, $key, $lease, $resultRef);
    }

    public static function release(string $scope, string $key, string $lease): bool
    {
        return self::store()->release($scope, $key, $lease);
    }
}
