<?php

namespace BadOtter\UltimateCommerce\RateLimit;

use BadOtter\UltimateCommerce\Contracts\RateLimiter;

defined('ABSPATH') || exit;

final class RateLimit
{
    private static ?RateLimiter $defaultLimiter = null;

    public static function limiter(): RateLimiter
    {
        if (!self::$defaultLimiter) {
            self::$defaultLimiter = new TransientRateLimiter();
        }
        $limiter = apply_filters('ultimate_commerce_rate_limiter', self::$defaultLimiter);
        return $limiter instanceof RateLimiter ? $limiter : self::$defaultLimiter;
    }

    public static function hit(string $scope, string $subject, int $limit, int $windowSeconds)
    {
        return self::limiter()->hit($scope, $subject, $limit, $windowSeconds);
    }
}
