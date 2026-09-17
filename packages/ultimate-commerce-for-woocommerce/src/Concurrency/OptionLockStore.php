<?php

namespace BadOtter\UltimateCommerce\Concurrency;

use BadOtter\UltimateCommerce\Contracts\LockStore;

defined('ABSPATH') || exit;

final class OptionLockStore implements LockStore
{
    private const PREFIX = 'uc_lock_';
    private const DELETE_HOOK = 'uc_lock_store_delete';
    private const MIN_TTL = 5;
    private const MAX_TTL = 86400;

    public static function hooks(): void
    {
        add_action(self::DELETE_HOOK, array(__CLASS__, 'deleteScheduled'), 10, 2);
    }

    /** @return string|false|\WP_Error */
    public function acquire(string $scope, string $key, int $ttlSeconds = 300)
    {
        if (!self::validScope($scope) || !self::validKey($key)) {
            return self::error('uc_lock_key_invalid', __('Lock scope or key is invalid.', 'ultimate-commerce-for-woocommerce'));
        }
        if ($ttlSeconds < self::MIN_TTL || $ttlSeconds > self::MAX_TTL) {
            return self::error('uc_lock_ttl_invalid', __('Lock lifetime is outside the allowed range.', 'ultimate-commerce-for-woocommerce'));
        }

        $option = self::optionName($scope, $key);
        $now = time();
        $existing = get_option($option, null);
        if (is_array($existing) && (int) ($existing['expires'] ?? 0) > $now) {
            return false;
        }
        if ($existing !== null) {
            delete_option($option);
        }

        try {
            $lease = bin2hex(random_bytes(16));
        } catch (\Throwable $exception) {
            return self::error('uc_lock_entropy', __('Lock entropy is unavailable.', 'ultimate-commerce-for-woocommerce'));
        }

        $expires = $now + $ttlSeconds;
        if (!add_option($option, array('expires' => $expires, 'lease' => $lease), '', false)) {
            return false;
        }

        if (function_exists('wp_schedule_single_event')) {
            wp_schedule_single_event($expires, self::DELETE_HOOK, array($option, $lease));
        }

        return $lease;
    }

    public function release(string $scope, string $key, string $lease): bool
    {
        if (!self::validScope($scope) || !self::validKey($key) || !self::validLease($lease)) {
            return false;
        }

        $option = self::optionName($scope, $key);
        $record = get_option($option, null);
        if (!is_array($record)
            || (int) ($record['expires'] ?? 0) <= time()
            || !hash_equals((string) ($record['lease'] ?? ''), $lease)) {
            return false;
        }

        return delete_option($option);
    }

    public static function deleteScheduled(string $option, string $lease): void
    {
        if (!str_starts_with($option, self::PREFIX) || !self::validLease($lease)) {
            return;
        }

        $record = get_option($option, null);
        if (!is_array($record)
            || (int) ($record['expires'] ?? 0) > time()
            || !hash_equals((string) ($record['lease'] ?? ''), $lease)) {
            return;
        }

        delete_option($option);
    }

    private static function optionName(string $scope, string $key): string
    {
        return self::PREFIX . hash('sha256', $scope . "\0" . $key);
    }

    private static function validScope(string $scope): bool
    {
        return (bool) preg_match('/^[a-z][a-z0-9_.:-]{0,63}$/', $scope);
    }

    private static function validKey(string $key): bool
    {
        return $key !== '' && strlen($key) <= 191 && !preg_match('/[\x00-\x1F\x7F]/', $key);
    }

    private static function validLease(string $lease): bool
    {
        return (bool) preg_match('/^[a-f0-9]{32}$/', $lease);
    }

    private static function error(string $code, string $message): \WP_Error
    {
        return new \WP_Error($code, $message);
    }
}
