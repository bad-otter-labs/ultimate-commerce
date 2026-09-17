<?php

namespace BadOtter\UltimateCommerce\Http;

defined('ABSPATH') || exit;

final class ProviderHttp
{
    private const MAX_TIMEOUT = 20;
    private const MAX_RESPONSE_BYTES = 2097152;
    private const METHODS = array('GET', 'POST', 'PUT', 'PATCH', 'DELETE', 'HEAD');

    /** @param array<int, string> $allowedAuthorities
     *  @param array<string, mixed> $args
     *  @return array|\WP_Error
     */
    public static function request(string $method, string $url, array $allowedAuthorities, array $args = array())
    {
        $method = strtoupper(trim($method));
        if (!in_array($method, self::METHODS, true)) {
            return self::error('uc_provider_http_method', __('Provider HTTP method is not allowed.', 'ultimate-commerce-for-woocommerce'));
        }

        $authority = self::validatedAuthority($url, $allowedAuthorities);
        if ($authority instanceof \WP_Error) {
            return $authority;
        }
        if (!wp_http_validate_url($url)) {
            return self::error('uc_provider_http_unsafe_url', __('Provider URL failed WordPress safety validation.', 'ultimate-commerce-for-woocommerce'));
        }

        $request = $args;
        $request['method'] = $method;
        $request['timeout'] = max(1, min(self::MAX_TIMEOUT, (int) ($args['timeout'] ?? 10)));
        $request['redirection'] = 0;
        $request['reject_unsafe_urls'] = true;
        $request['sslverify'] = true;
        $request['stream'] = false;
        $request['limit_response_size'] = max(1, min(self::MAX_RESPONSE_BYTES, (int) ($args['limit_response_size'] ?? self::MAX_RESPONSE_BYTES)));
        unset($request['filename']);

        $headers = is_array($request['headers'] ?? null) ? $request['headers'] : array();
        foreach (array_keys($headers) as $name) {
            if (strtolower((string) $name) === 'host') {
                unset($headers[$name]);
            }
        }
        if (!isset($headers['Accept'])) {
            $headers['Accept'] = 'application/json';
        }
        $request['headers'] = $headers;

        if (!isset($request['user-agent'])) {
            $version = defined('ULTIMATE_COMMERCE_VERSION') ? ULTIMATE_COMMERCE_VERSION : 'development';
            $request['user-agent'] = 'Ultimate Commerce/' . $version;
        }

        return wp_safe_remote_request($url, $request);
    }

    /** @param array<int, string> $allowedAuthorities
     *  @return string|\WP_Error
     */
    private static function validatedAuthority(string $url, array $allowedAuthorities)
    {
        $parts = wp_parse_url($url);
        if (!is_array($parts) || strtolower((string) ($parts['scheme'] ?? '')) !== 'https') {
            return self::error('uc_provider_http_https_required', __('Provider URLs must use HTTPS.', 'ultimate-commerce-for-woocommerce'));
        }
        if (!empty($parts['user']) || !empty($parts['pass']) || !empty($parts['fragment'])) {
            return self::error('uc_provider_http_url_invalid', __('Provider URLs must not contain credentials or fragments.', 'ultimate-commerce-for-woocommerce'));
        }

        $host = strtolower(rtrim((string) ($parts['host'] ?? ''), '.'));
        if ($host === '' || filter_var($host, FILTER_VALIDATE_IP)) {
            return self::error('uc_provider_http_host_invalid', __('Provider URLs must use an allowlisted DNS host.', 'ultimate-commerce-for-woocommerce'));
        }
        $port = isset($parts['port']) ? (int) $parts['port'] : 443;
        if ($port < 1 || $port > 65535) {
            return self::error('uc_provider_http_port_invalid', __('Provider URL port is invalid.', 'ultimate-commerce-for-woocommerce'));
        }
        $authority = $host . ($port === 443 ? '' : ':' . $port);

        $allowed = array();
        foreach ($allowedAuthorities as $candidate) {
            $candidate = strtolower(trim((string) $candidate));
            if ($candidate !== '' && !str_contains($candidate, '://') && !str_contains($candidate, '/')) {
                $allowed[] = rtrim($candidate, '.');
            }
        }
        if (!in_array($authority, $allowed, true)) {
            return self::error('uc_provider_http_host_forbidden', __('Provider host is not allowlisted.', 'ultimate-commerce-for-woocommerce'));
        }

        return $authority;
    }

    private static function error(string $code, string $message): \WP_Error
    {
        return new \WP_Error($code, $message);
    }
}
