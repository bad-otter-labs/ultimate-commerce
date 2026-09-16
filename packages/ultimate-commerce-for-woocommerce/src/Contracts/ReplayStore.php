<?php

namespace BadOtter\UltimateCommerce\Contracts;

defined('ABSPATH') || exit;

interface ReplayStore
{
    /** @return string|false|\WP_Error Lease token on success, false when already claimed. */
    public function claim(string $scope, string $eventId, int $ttlSeconds = 86400);

    public function release(string $scope, string $eventId, string $lease): bool;
}
