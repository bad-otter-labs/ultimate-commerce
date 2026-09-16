<?php

namespace BadOtter\UltimateCommerce\Security;

use BadOtter\UltimateCommerce\Contracts\ReplayStore;

defined('ABSPATH') || exit;

final class SignedWebhook
{
    private const DEFAULT_TOLERANCE = 300;
    private const DEFAULT_REPLAY_TTL = 86400;
    private const MAX_BODY_BYTES = 2097152;

    /** @return array<string, mixed>|\WP_Error */
    public static function verifyAndClaim(
        string $scope,
        string $eventId,
        string $timestamp,
        string $signature,
        string $rawBody,
        string $secret,
        ReplayStore $replayStore,
        int $toleranceSeconds = self::DEFAULT_TOLERANCE,
        int $replayTtlSeconds = self::DEFAULT_REPLAY_TTL,
        ?int $now = null
    ) {
        $verified = self::verify($scope, $eventId, $timestamp, $signature, $rawBody, $secret, $toleranceSeconds, $now);
        if ($verified instanceof \WP_Error) {
            return $verified;
        }

        $lease = $replayStore->claim($scope, $eventId, $replayTtlSeconds);
        if ($lease instanceof \WP_Error) {
            return $lease;
        }
        if ($lease === false) {
            return self::error('uc_webhook_replay', 'This webhook event has already been accepted.', 409);
        }

        $verified['lease'] = $lease;
        return $verified;
    }

    /** @return array<string, mixed>|\WP_Error */
    public static function verify(
        string $scope,
        string $eventId,
        string $timestamp,
        string $signature,
        string $rawBody,
        string $secret,
        int $toleranceSeconds = self::DEFAULT_TOLERANCE,
        ?int $now = null
    ) {
        if (!preg_match('/^[a-z][a-z0-9_.:-]{0,63}$/', $scope)
            || !preg_match('/^[A-Za-z0-9][A-Za-z0-9_.:-]{0,190}$/', $eventId)) {
            return self::error('uc_webhook_identity', 'Webhook identity is invalid.', 400);
        }
        if ($secret === '' || strlen($secret) < 16) {
            return self::error('uc_webhook_secret', 'Webhook secret is not configured securely.', 500);
        }
        if ($toleranceSeconds < 30 || $toleranceSeconds > 3600) {
            return self::error('uc_webhook_tolerance', 'Webhook timestamp tolerance is invalid.', 500);
        }
        if (strlen($rawBody) > self::MAX_BODY_BYTES) {
            return self::error('uc_webhook_body_too_large', 'Webhook payload is too large.', 413);
        }
        if (!preg_match('/^[0-9]{10,}$/', $timestamp)) {
            return self::error('uc_webhook_timestamp', 'Webhook timestamp is invalid.', 401);
        }

        $now = $now ?? time();
        $eventTime = (int) $timestamp;
        if (abs($now - $eventTime) > $toleranceSeconds) {
            return self::error('uc_webhook_timestamp', 'Webhook timestamp is outside the accepted window.', 401);
        }

        $candidates = self::signatureCandidates($signature);
        if ($candidates === array()) {
            return self::error('uc_webhook_signature', 'Webhook signature is invalid.', 401);
        }

        $message = $timestamp . '.' . $eventId . '.' . $rawBody;
        $expected = hash_hmac('sha256', $message, $secret);
        $matched = false;
        foreach ($candidates as $candidate) {
            if (hash_equals($expected, $candidate)) {
                $matched = true;
                break;
            }
        }
        if (!$matched) {
            return self::error('uc_webhook_signature', 'Webhook signature verification failed.', 401);
        }

        return array(
            'scope' => $scope,
            'event_id' => $eventId,
            'timestamp' => $eventTime,
            'body_sha256' => hash('sha256', $rawBody),
        );
    }

    public static function release(ReplayStore $replayStore, string $scope, string $eventId, string $lease): bool
    {
        return $replayStore->release($scope, $eventId, $lease);
    }

    /** @return array<int, string> */
    private static function signatureCandidates(string $signature): array
    {
        $candidates = array();
        foreach (explode(',', $signature) as $part) {
            $part = trim($part);
            if (str_starts_with($part, 'v1=')) {
                $value = strtolower(substr($part, 3));
                if (preg_match('/^[a-f0-9]{64}$/', $value)) {
                    $candidates[] = $value;
                }
            }
        }
        return $candidates;
    }

    private static function error(string $code, string $message, int $status): \WP_Error
    {
        return new \WP_Error($code, __($message, 'ultimate-commerce-for-woocommerce'), array('status' => $status));
    }
}
