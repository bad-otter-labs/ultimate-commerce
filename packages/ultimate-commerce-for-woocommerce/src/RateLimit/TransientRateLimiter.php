<?php

namespace BadOtter\UltimateCommerce\RateLimit;

use BadOtter\UltimateCommerce\Contracts\RateLimiter;

defined('ABSPATH') || exit;

final class TransientRateLimiter implements RateLimiter
{
    private const PREFIX = 'uc_rl_';
    private const MAX_LIMIT = 10000;
    private const MAX_WINDOW = 86400;

    public function hit(string $scope, string $subject, int $limit, int $windowSeconds)
    {
        if (!self::validScope($scope) || !self::validSubject($subject)) {
            return self::error('uc_rate_limit_key_invalid', __('Rate-limit scope or subject is invalid.', 'ultimate-commerce-for-woocommerce'));
        }
        if ($limit < 1 || $limit > self::MAX_LIMIT || $windowSeconds < 1 || $windowSeconds > self::MAX_WINDOW) {
            return self::error('uc_rate_limit_policy_invalid', __('Rate-limit policy is outside the allowed range.', 'ultimate-commerce-for-woocommerce'));
        }

        $now = time();
        $bucket = intdiv($now, $windowSeconds);
        $resetAt = ($bucket + 1) * $windowSeconds;
        $name = self::PREFIX . hash('sha256', $scope . "\0" . $subject . "\0" . $bucket);
        $record = get_transient($name);
        $count = is_array($record) ? (int) ($record['count'] ?? 0) : 0;
        $count++;

        $retryAfter = max(1, $resetAt - $now);
        set_transient($name, array('count' => $count, 'reset_at' => $resetAt), $retryAfter + 5);

        $allowed = $count <= $limit;
        return array(
            'allowed' => $allowed,
            'limit' => $limit,
            'remaining' => $allowed ? max(0, $limit - $count) : 0,
            'retry_after' => $allowed ? 0 : $retryAfter,
        );
    }

    private static function validScope(string $scope): bool
    {
        return (bool) preg_match('/^[a-z][a-z0-9_.:-]{0,63}$/', $scope);
    }

    private static function validSubject(string $subject): bool
    {
        return $subject !== '' && strlen($subject) <= 512 && !preg_match('/[\x00-\x1F\x7F]/', $subject);
    }

    private static function error(string $code, string $message): \WP_Error
    {
        return new \WP_Error($code, $message);
    }
}
