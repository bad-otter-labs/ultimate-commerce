<?php

namespace BadOtter\UltimateCommerce\Contracts;

defined('ABSPATH') || exit;

interface LockStore
{
    /** @return string|false|\WP_Error Lease token, false when already locked. */
    public function acquire(string $scope, string $key, int $ttlSeconds = 300);

    public function release(string $scope, string $key, string $lease): bool;
}
