<?php

namespace BadOtter\UltimateCommerce\Idempotency;

use BadOtter\UltimateCommerce\Contracts\IdempotencyStore;

defined('ABSPATH') || exit;

final class OptionIdempotencyStore implements IdempotencyStore
{
    public const STATUS_CLAIMED = 'claimed';
    public const STATUS_IN_PROGRESS = 'in_progress';
    public const STATUS_COMPLETED = 'completed';

    private const PREFIX = 'uc_idem_';
    private const DELETE_HOOK = 'uc_idempotency_store_delete';
    private const MIN_TTL = 60;
    private const MAX_TTL = 604800;

    public static function hooks(): void
    {
        add_action(self::DELETE_HOOK, array(__CLASS__, 'deleteScheduled'), 10, 2);
    }

    public function claim(string $scope, string $key, int $ttlSeconds = 86400)
    {
        if (!self::validScope($scope) || !self::validKey($key)) {
            return self::error('uc_idempotency_key_invalid', 'Idempotency scope or key is invalid.');
        }
        if ($ttlSeconds < self::MIN_TTL || $ttlSeconds > self::MAX_TTL) {
            return self::error('uc_idempotency_ttl_invalid', 'Idempotency lifetime is outside the allowed range.');
        }

        $option = self::optionName($scope, $key);
        $now = time();
        $existing = get_option($option, null);
        if (is_array($existing) && (int) ($existing['expires'] ?? 0) > $now) {
            return self::publicRecord($existing);
        }
        if ($existing !== null) {
            delete_option($option);
        }

        try {
            $lease = bin2hex(random_bytes(16));
        } catch (\Throwable $exception) {
            return self::error('uc_idempotency_entropy', 'Idempotency entropy is unavailable.');
        }

        $expires = $now + $ttlSeconds;
        $record = array(
            'state' => self::STATUS_IN_PROGRESS,
            'expires' => $expires,
            'lease' => $lease,
            'result_ref' => '',
        );
        if (!add_option($option, $record, '', false)) {
            $raced = get_option($option, null);
            if (is_array($raced) && (int) ($raced['expires'] ?? 0) > $now) {
                return self::publicRecord($raced);
            }
            return self::error('uc_idempotency_claim_failed', 'Idempotency claim could not be established.');
        }

        if (function_exists('wp_schedule_single_event')) {
            wp_schedule_single_event($expires, self::DELETE_HOOK, array($option, $lease));
        }

        return array('status' => self::STATUS_CLAIMED, 'lease' => $lease, 'result_ref' => '');
    }

    public function complete(string $scope, string $key, string $lease, string $resultRef = ''): bool
    {
        if (!self::validScope($scope) || !self::validKey($key) || !self::validLease($lease) || !self::validResultRef($resultRef)) {
            return false;
        }

        $option = self::optionName($scope, $key);
        $record = get_option($option, null);
        if (!is_array($record)
            || ($record['state'] ?? '') !== self::STATUS_IN_PROGRESS
            || (int) ($record['expires'] ?? 0) <= time()
            || !hash_equals((string) ($record['lease'] ?? ''), $lease)) {
            return false;
        }

        $record['state'] = self::STATUS_COMPLETED;
        $record['result_ref'] = $resultRef;
        $record['completed_at'] = time();
        return update_option($option, $record, false);
    }

    public function release(string $scope, string $key, string $lease): bool
    {
        if (!self::validScope($scope) || !self::validKey($key) || !self::validLease($lease)) {
            return false;
        }

        $option = self::optionName($scope, $key);
        $record = get_option($option, null);
        if (!is_array($record)
            || ($record['state'] ?? '') !== self::STATUS_IN_PROGRESS
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

    /** @param array<string, mixed> $record
     *  @return array{status:string,lease:string,result_ref:string}
     */
    private static function publicRecord(array $record): array
    {
        $state = (string) ($record['state'] ?? self::STATUS_IN_PROGRESS);
        if ($state === self::STATUS_COMPLETED) {
            return array(
                'status' => self::STATUS_COMPLETED,
                'lease' => '',
                'result_ref' => (string) ($record['result_ref'] ?? ''),
            );
        }

        return array('status' => self::STATUS_IN_PROGRESS, 'lease' => '', 'result_ref' => '');
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

    private static function validResultRef(string $resultRef): bool
    {
        return strlen($resultRef) <= 191 && !preg_match('/[\x00-\x1F\x7F]/', $resultRef);
    }

    private static function error(string $code, string $message): \WP_Error
    {
        return new \WP_Error($code, __($message, 'ultimate-commerce-for-woocommerce'));
    }
}
