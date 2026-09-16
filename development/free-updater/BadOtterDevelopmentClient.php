<?php

namespace BadOtter\UltimateCommerce\DevelopmentUpdates;

use BadOtter\UltimateCommerce\Http\ProviderHttp;

defined('ABSPATH') || exit;

final class BadOtterDevelopmentClient
{
    private const BASE = 'https://api.badotter.io/v1';
    private const AUTHORITY = 'api.badotter.io';
    private const PRODUCT = 'ultimate-commerce-for-woocommerce-dev';
    private const MODULE = 'core';

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

    /** @return array{success:bool,http:int,message:string,data:array<string,mixed>} */
    public function updateLookup(bool $fresh = false): array
    {
        $query = array(
            'site' => self::siteOrigin(),
            'currentVersion' => ULTIMATE_COMMERCE_VERSION,
            'coreVersion' => ULTIMATE_COMMERCE_VERSION,
            'wordpressVersion' => get_bloginfo('version'),
            'phpVersion' => PHP_VERSION,
            'channel' => 'stable',
        );
        if ($fresh) {
            $query['_refresh'] = (string) microtime(true);
        }

        $url = add_query_arg($query, self::BASE . '/updates/' . self::PRODUCT . '/' . self::MODULE);
        $headers = array(
            'Accept' => 'application/json',
            'X-BadOtter-Site' => self::siteOrigin(),
            'X-BadOtter-Product' => self::PRODUCT,
            'X-BadOtter-Module' => self::MODULE,
            'X-BadOtter-Module-Version' => ULTIMATE_COMMERCE_VERSION,
        );
        if ($fresh) {
            $headers['Cache-Control'] = 'no-cache, no-store, max-age=0';
            $headers['Pragma'] = 'no-cache';
        }

        $response = ProviderHttp::request('GET', $url, array(self::AUTHORITY), array(
            'timeout' => 20,
            'headers' => $headers,
            'user-agent' => 'Ultimate Commerce Development/' . ULTIMATE_COMMERCE_VERSION,
        ));

        if (is_wp_error($response)) {
            return array(
                'success' => false,
                'http' => 0,
                'message' => sanitize_text_field($response->get_error_message()),
                'data' => array(),
            );
        }

        $http = (int) wp_remote_retrieve_response_code($response);
        $decoded = json_decode((string) wp_remote_retrieve_body($response), true);
        if (!is_array($decoded)) {
            $decoded = array();
        }

        if ($http >= 200 && $http < 300 && (!isset($decoded['success']) || true === $decoded['success'])) {
            return array(
                'success' => true,
                'http' => $http,
                'message' => '',
                'data' => is_array($decoded['data'] ?? null) ? $decoded['data'] : $decoded,
            );
        }

        return array(
            'success' => false,
            'http' => $http,
            'message' => sanitize_text_field((string) ($decoded['message'] ?? ($decoded['error']['message'] ?? 'Bad Otter development update lookup failed.'))),
            'data' => array(),
        );
    }
}
