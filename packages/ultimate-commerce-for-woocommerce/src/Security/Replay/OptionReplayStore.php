<?php

namespace BadOtter\UltimateCommerce\Security\Replay;

use BadOtter\UltimateCommerce\Contracts\ReplayStore;

defined('ABSPATH') || exit;

final class OptionReplayStore implements ReplayStore
{
    private const PREFIX = 'uc_replay_';
    private const DELETE_HOOK = 'uc_replay_store_delete';
    private const MIN_TTL = 60;
    private const MAX_TTL = 604800;

    public static function hooks(): void
    {
        add_action(self::DELETE_HOOK, array(__CLASS__, 'deleteScheduled'), 10, 2);
    }

    /** @return string|false|\WP_Error */
    public function claim(string $scope, string $eventId, int $ttlSeconds = 86400)
    {
        if (!self::validScope($scope) || !self::validEventId($eventId)) {
            return self::error('uc_replay_key_invalid', 'Replay protection key is invalid.');
        }
        if ($ttlSeconds < self::MIN_TTL || $ttlSeconds > self::MAX_TTL) {
            return self::error('uc_replay_ttl_invalid', 'Replay protection lifetime is outside the allowed range.');
        }

        $name = self::optionName($scope, $eventId);
        $now = time();
        $existing = get_option($name, null);
        if (is_array($existing) && (int) ($existing['expires'] ?? 0) > $now) {
            return false;
        }
        if ($existing !== null) {
            delete_option($name);
        }

        try {
            $lease = bin2hex(random_bytes(16));
        } catch (\Throwable $exception) {
            return self::error('uc_replay_entropy', 'Replay protection entropy is unavailable.');
        }

        $expires = $now + $ttlSeconds;
        $record = array('expires' => $expires, 'lease' => $lease);
        if (!add_option($name, $record, '', false)) {
            return false;
        }

        if (function_exists('wp_schedule_single_event')) {
            wp_schedule_single_event($expires, self::DELETE_HOOK, array($name, $lease));
        }

        return $lease;
    }

    public function release(string $scope, string $eventId, string $lease): bool
    {
        if (!self::validScope($scope) || !self::validEventId($eventId) || !preg_match('/^[a-f0-9]{32}$/', $lease)) {
            return false;
        }

        $name = self::optionName($scope, $eventId);
        $record = get_option($name, null);
        if (!is_array($record) || !hash_equals((string) ($record['lease'] ?? ''), $lease)) {
            return false;
        }
        if ((int) ($record['expires'] ?? 0) <= time()) {
            return false;
        }

        return delete_option($name);
    }

    public static function deleteScheduled(string $optionName, string $lease): void
    {
        if (!str_starts_with($optionName, self::PREFIX) || !preg_match('/^[a-f0-9]{32}$/', $lease)) {
            return;
        }

        $record = get_option($optionName, null);
        if (!is_array($record) || !hash_equals((string) ($record['lease'] ?? ''), $lease)) {
            return;
        }
        if ((int) ($record['expires'] ?? 0) > time()) {
            return;
        }

        delete_option($optionName);
    }

    private static function optionName(string $scope, string $eventId): string
    {
        return self::PREFIX . hash('sha256', $scope . "\0" . $eventId);
    }

    private static function validScope(string $scope): bool
    {
        return (bool) preg_match('/^[a-z][a-z0-9_.:-]{0,63}$/', $scope);
    }

    private static function validEventId(string $eventId): bool
    {
        return (bool) preg_match('/^[A-Za-z0-9][A-Za-z0-9_.:-]{0,190}$/', $eventId);
    }

    private static function error(string $code, string $message): \WP_Error
    {
        return new \WP_Error($code, __($message, 'ultimate-commerce-for-woocommerce'));
    }
}
