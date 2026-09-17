<?php

namespace BadOtter\UltimateCommerce\Security;

defined('ABSPATH') || exit;

final class GuestToken
{
    private const PREFIX = 'ucg1';
    private const MIN_TTL = 60;
    private const MAX_TTL = 604800;
    private const MAX_TOKEN_LENGTH = 4096;
    private const MAX_CLAIMS_JSON = 1024;

    /** @param array<string, scalar|null> $claims
     *  @return string|\WP_Error
     */
    public static function issue(string $purpose, string $subject, int $ttlSeconds = 900, array $claims = array())
    {
        $problem = self::validateInputs($purpose, $subject, $ttlSeconds, $claims);
        if ($problem instanceof \WP_Error) {
            return $problem;
        }

        try {
            $tokenId = bin2hex(random_bytes(16));
        } catch (\Throwable $exception) {
            return self::error('uc_guest_token_entropy', __('Secure token entropy is unavailable.', 'ultimate-commerce-for-woocommerce'), 500);
        }

        $now = time();
        $payload = array(
            'v' => 1,
            'pur' => $purpose,
            'sub' => $subject,
            'iat' => $now,
            'exp' => $now + $ttlSeconds,
            'jti' => $tokenId,
            'clm' => $claims,
        );
        $json = wp_json_encode($payload);
        if (!is_string($json)) {
            return self::error('uc_guest_token_encode', __('The guest token could not be encoded.', 'ultimate-commerce-for-woocommerce'), 500);
        }

        $encoded = self::base64UrlEncode($json);
        $signed = self::PREFIX . '.' . $encoded;
        $signature = hash_hmac('sha256', $signed, self::key($purpose), true);

        return $signed . '.' . self::base64UrlEncode($signature);
    }

    /** @return array<string, mixed>|\WP_Error */
    public static function verify(string $token, string $purpose, ?string $expectedSubject = null, ?int $now = null)
    {
        if (!self::validPurpose($purpose) || strlen($token) > self::MAX_TOKEN_LENGTH) {
            return self::error('uc_guest_token_invalid', __('The guest token is invalid.', 'ultimate-commerce-for-woocommerce'), 401);
        }

        $parts = explode('.', $token);
        if (count($parts) !== 3 || $parts[0] !== self::PREFIX) {
            return self::error('uc_guest_token_invalid', __('The guest token is invalid.', 'ultimate-commerce-for-woocommerce'), 401);
        }

        $payloadBytes = self::base64UrlDecode($parts[1]);
        $signature = self::base64UrlDecode($parts[2]);
        if ($payloadBytes === null || $signature === null || strlen($signature) !== 32) {
            return self::error('uc_guest_token_invalid', __('The guest token is invalid.', 'ultimate-commerce-for-woocommerce'), 401);
        }

        $expected = hash_hmac('sha256', self::PREFIX . '.' . $parts[1], self::key($purpose), true);
        if (!hash_equals($expected, $signature)) {
            return self::error('uc_guest_token_invalid', __('The guest token signature is invalid.', 'ultimate-commerce-for-woocommerce'), 401);
        }

        $payload = json_decode($payloadBytes, true);
        if (!is_array($payload) || (int) ($payload['v'] ?? 0) !== 1) {
            return self::error('uc_guest_token_invalid', __('The guest token payload is invalid.', 'ultimate-commerce-for-woocommerce'), 401);
        }

        $tokenPurpose = (string) ($payload['pur'] ?? '');
        $subject = (string) ($payload['sub'] ?? '');
        $issuedAt = (int) ($payload['iat'] ?? 0);
        $expiresAt = (int) ($payload['exp'] ?? 0);
        $tokenId = (string) ($payload['jti'] ?? '');
        $claims = $payload['clm'] ?? array();
        $now = $now ?? time();

        if ($tokenPurpose !== $purpose || !self::validSubject($subject) || !preg_match('/^[a-f0-9]{32}$/', $tokenId)) {
            return self::error('uc_guest_token_invalid', __('The guest token payload is invalid.', 'ultimate-commerce-for-woocommerce'), 401);
        }
        if (!is_array($claims) || self::validateClaims($claims) instanceof \WP_Error) {
            return self::error('uc_guest_token_invalid', __('The guest token claims are invalid.', 'ultimate-commerce-for-woocommerce'), 401);
        }
        if ($issuedAt <= 0 || $expiresAt <= $issuedAt || $issuedAt > $now + 60) {
            return self::error('uc_guest_token_invalid', __('The guest token time window is invalid.', 'ultimate-commerce-for-woocommerce'), 401);
        }
        if ($expiresAt <= $now) {
            return self::error('uc_guest_token_expired', __('The guest token has expired.', 'ultimate-commerce-for-woocommerce'), 401);
        }
        if ($expectedSubject !== null && !hash_equals($expectedSubject, $subject)) {
            return self::error('uc_guest_token_subject', __('The guest token does not match this resource.', 'ultimate-commerce-for-woocommerce'), 403);
        }

        return array(
            'subject' => $subject,
            'claims' => $claims,
            'issued_at' => $issuedAt,
            'expires_at' => $expiresAt,
            'token_id' => $tokenId,
        );
    }

    /** @param array<string, scalar|null> $claims
     *  @return true|\WP_Error
     */
    private static function validateInputs(string $purpose, string $subject, int $ttlSeconds, array $claims)
    {
        if (!self::validPurpose($purpose)) {
            return self::error('uc_guest_token_purpose', __('Guest token purpose is invalid.', 'ultimate-commerce-for-woocommerce'), 400);
        }
        if (!self::validSubject($subject)) {
            return self::error('uc_guest_token_subject', __('Guest token subject is invalid.', 'ultimate-commerce-for-woocommerce'), 400);
        }
        if ($ttlSeconds < self::MIN_TTL || $ttlSeconds > self::MAX_TTL) {
            return self::error('uc_guest_token_ttl', __('Guest token lifetime is outside the allowed range.', 'ultimate-commerce-for-woocommerce'), 400);
        }

        return self::validateClaims($claims);
    }

    /** @param array<mixed> $claims
     *  @return true|\WP_Error
     */
    private static function validateClaims(array $claims)
    {
        foreach ($claims as $name => $value) {
            if (!is_string($name) || !preg_match('/^[A-Za-z][A-Za-z0-9_.:-]{0,63}$/', $name)) {
                return self::error('uc_guest_token_claims', __('Guest token claim names are invalid.', 'ultimate-commerce-for-woocommerce'), 400);
            }
            if ($value !== null && !is_scalar($value)) {
                return self::error('uc_guest_token_claims', __('Guest token claims must contain scalar values only.', 'ultimate-commerce-for-woocommerce'), 400);
            }
        }

        $json = wp_json_encode($claims);
        if (!is_string($json) || strlen($json) > self::MAX_CLAIMS_JSON) {
            return self::error('uc_guest_token_claims', __('Guest token claims are too large.', 'ultimate-commerce-for-woocommerce'), 400);
        }

        return true;
    }

    private static function validPurpose(string $purpose): bool
    {
        return (bool) preg_match('/^[a-z][a-z0-9_.:-]{0,63}$/', $purpose);
    }

    private static function validSubject(string $subject): bool
    {
        return $subject !== '' && strlen($subject) <= 191 && !preg_match('/[\x00-\x1F\x7F]/', $subject);
    }

    private static function key(string $purpose): string
    {
        return hash_hmac('sha256', 'ultimate-commerce:guest-token:v1:' . $purpose, (string) wp_salt('auth'), true);
    }

    private static function base64UrlEncode(string $value): string
    {
        return rtrim(strtr(base64_encode($value), '+/', '-_'), '=');
    }

    private static function base64UrlDecode(string $value): ?string
    {
        if ($value === '' || preg_match('/[^A-Za-z0-9_-]/', $value)) {
            return null;
        }
        $padding = (4 - (strlen($value) % 4)) % 4;
        $decoded = base64_decode(strtr($value, '-_', '+/') . str_repeat('=', $padding), true);
        return is_string($decoded) ? $decoded : null;
    }

    private static function error(string $code, string $message, int $status): \WP_Error
    {
        return new \WP_Error($code, $message, array('status' => $status));
    }
}
