<?php

namespace BadOtter\UltimateCommerce\Updates;

defined('ABSPATH') || exit;

final class BadOtterClient
{
    private const DEFAULT_BASE = 'https://api.badotter.io/v1';
    private const PRODUCT = 'ultimate-commerce';

    public static function baseUrl(): string
    {
        $base = defined('ULTIMATE_COMMERCE_BAD_OTTER_API_BASE')
            ? (string) ULTIMATE_COMMERCE_BAD_OTTER_API_BASE
            : self::DEFAULT_BASE;

        return untrailingslashit((string) apply_filters('uc_bad_otter_api_base', $base));
    }

    public static function siteOrigin(): string
    {
        $parts = wp_parse_url(home_url('/'));
        if (!is_array($parts) || empty($parts['host'])) {
            return '';
        }

        $scheme = strtolower((string) ($parts['scheme'] ?? 'https'));
        $origin = $scheme . '://' . strtolower((string) $parts['host']);
        $port = isset($parts['port']) ? (int) $parts['port'] : 0;
        if ($port && !(('https' === $scheme && 443 === $port) || ('http' === $scheme && 80 === $port))) {
            $origin .= ':' . $port;
        }
        return $origin;
    }

    public function updateLookup(string $currentVersion): array
    {
        $query = array(
            'site' => self::siteOrigin(),
            'currentVersion' => sanitize_text_field($currentVersion),
            'coreVersion' => ULTIMATE_COMMERCE_VERSION,
            'wordpressVersion' => get_bloginfo('version'),
            'phpVersion' => PHP_VERSION,
            'channel' => 'stable',
        );

        return $this->get('/updates/' . self::PRODUCT . '/core', $query);
    }

    public function absoluteDownloadUrl(string $value): string
    {
        $value = trim($value);
        if ($value === '') {
            return '';
        }

        if (preg_match('#^https://#i', $value)) {
            $candidate = wp_parse_url($value);
            $base = wp_parse_url(self::baseUrl());
            if (!is_array($candidate) || !is_array($base) || empty($candidate['host']) || empty($base['host'])) {
                return '';
            }
            if (strtolower((string) $candidate['host']) !== strtolower((string) $base['host'])) {
                return '';
            }
            return esc_url_raw($value);
        }

        if (str_starts_with($value, '/')) {
            return esc_url_raw(self::baseUrl() . $value);
        }

        return esc_url_raw(self::baseUrl() . '/packages/download/' . rawurlencode($value));
    }

    private function get(string $path, array $query): array
    {
        $url = add_query_arg(array_filter($query, static fn($value) => $value !== '' && $value !== null), self::baseUrl() . '/' . ltrim($path, '/'));
        $response = wp_remote_get($url, array(
            'timeout' => 20,
            'redirection' => 3,
            'sslverify' => true,
            'headers' => array('Accept' => 'application/json'),
            'user-agent' => 'Ultimate Commerce/' . ULTIMATE_COMMERCE_VERSION . '; ' . self::siteOrigin(),
        ));

        if (is_wp_error($response)) {
            return array('success' => false, 'message' => sanitize_text_field($response->get_error_message()), 'data' => array());
        }

        $decoded = json_decode(wp_remote_retrieve_body($response), true);
        if (!is_array($decoded) || empty($decoded['success'])) {
            return array(
                'success' => false,
                'message' => sanitize_text_field((string) ($decoded['message'] ?? 'Bad Otter update lookup failed.')),
                'data' => array(),
            );
        }

        return array('success' => true, 'message' => '', 'data' => is_array($decoded['data'] ?? null) ? $decoded['data'] : array());
    }
}
