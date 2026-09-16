<?php

namespace BadOtter\UltimateCommerce\Contracts;

defined('ABSPATH') || exit;

interface RateLimiter
{
    /**
     * @return array{allowed:bool,limit:int,remaining:int,retry_after:int}|\WP_Error
     */
    public function hit(string $scope, string $subject, int $limit, int $windowSeconds);
}
