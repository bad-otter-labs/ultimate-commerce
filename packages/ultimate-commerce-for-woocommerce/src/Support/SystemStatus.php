<?php

namespace BadOtter\UltimateCommerce\Support;

defined('ABSPATH') || exit;

final class SystemStatus
{
    public const SNAPSHOT_SCHEMA = 1;

    /**
     * Build a privacy-minimised support snapshot from public/runtime state.
     *
     * @param array<string, array<string, mixed>> $moduleStatuses Registered module status rows.
     * @return array<string, mixed>
     */
    public static function snapshot(array $moduleStatuses): array
    {
        $modules = self::modules($moduleStatuses);
        $counts = array(
            'registered' => count($modules),
            'active' => 0,
            'disabled' => 0,
            'blocked' => 0,
        );

        foreach ($modules as $module) {
            if (!empty($module['booted'])) {
                ++$counts['active'];
            }
            if (($module['status'] ?? '') === 'disabled') {
                ++$counts['disabled'];
            }
            if (($module['status'] ?? '') === 'blocked') {
                ++$counts['blocked'];
            }
        }

        $wordpressVersion = function_exists('get_bloginfo') ? (string) get_bloginfo('version') : 'Unavailable';
        $woocommerceVersion = defined('WC_VERSION') ? (string) WC_VERSION : 'Unavailable';
        $hposActive = class_exists(\Automattic\WooCommerce\Utilities\OrderUtil::class)
            ? (bool) \Automattic\WooCommerce\Utilities\OrderUtil::custom_orders_table_usage_is_enabled()
            : false;

        $snapshot = array(
            'schema' => self::SNAPSHOT_SCHEMA,
            'overall' => ($counts['blocked'] > 0 || $woocommerceVersion === 'Unavailable') ? 'attention' : 'healthy',
            'plugin' => array(
                'version' => self::constant('ULTIMATE_COMMERCE_VERSION'),
                'schema' => self::constant('ULTIMATE_COMMERCE_SCHEMA_VERSION'),
                'development_build' => defined('ULTIMATE_COMMERCE_DEVELOPMENT_BUILD') && ULTIMATE_COMMERCE_DEVELOPMENT_BUILD,
            ),
            'apis' => array(
                'module' => self::constant('ULTIMATE_COMMERCE_MODULE_API_VERSION'),
                'security' => self::constant('ULTIMATE_COMMERCE_SECURITY_API_VERSION'),
                'audit' => self::constant('ULTIMATE_COMMERCE_AUDIT_API_VERSION'),
                'privacy' => self::constant('ULTIMATE_COMMERCE_PRIVACY_API_VERSION'),
                'execution' => self::constant('ULTIMATE_COMMERCE_EXECUTION_API_VERSION'),
                'storefront' => self::constant('ULTIMATE_COMMERCE_STOREFRONT_API_VERSION'),
                'cart' => self::constant('ULTIMATE_COMMERCE_CART_API_VERSION'),
                'wishlist' => self::constant('ULTIMATE_COMMERCE_WISHLIST_API_VERSION'),
                'recently_viewed' => self::constant('ULTIMATE_COMMERCE_RECENTLY_VIEWED_API_VERSION'),
                'account' => self::constant('ULTIMATE_COMMERCE_ACCOUNT_API_VERSION'),
            ),
            'environment' => array(
                'wordpress' => $wordpressVersion,
                'woocommerce' => $woocommerceVersion,
                'php' => PHP_VERSION,
                'multisite' => function_exists('is_multisite') && is_multisite(),
                'external_object_cache' => function_exists('wp_using_ext_object_cache') && wp_using_ext_object_cache(),
                'wp_debug' => defined('WP_DEBUG') && WP_DEBUG,
                'wp_debug_log' => defined('WP_DEBUG_LOG') && (bool) WP_DEBUG_LOG,
                'wp_cron_disabled' => defined('DISABLE_WP_CRON') && DISABLE_WP_CRON,
                'memory_limit' => defined('WP_MEMORY_LIMIT') ? (string) WP_MEMORY_LIMIT : '',
                'max_memory_limit' => defined('WP_MAX_MEMORY_LIMIT') ? (string) WP_MAX_MEMORY_LIMIT : '',
            ),
            'compatibility' => array(
                'hpos_declared' => true,
                'hpos_active' => $hposActive,
                'cart_checkout_blocks_declared' => true,
                'action_scheduler_available' => function_exists('as_schedule_single_action'),
            ),
            'retention' => array(
                'delete_data_on_uninstall' => Settings::deleteDataOnUninstall(),
            ),
            'module_counts' => $counts,
            'modules' => $modules,
        );

        return $snapshot;
    }

    /** @param array<string, mixed> $snapshot */
    public static function supportJson(array $snapshot): string
    {
        $flags = JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES;
        $json = wp_json_encode($snapshot, $flags);

        return is_string($json) ? $json : '{}';
    }

    /**
     * @param array<string, array<string, mixed>> $statuses
     * @return array<string, array<string, mixed>>
     */
    private static function modules(array $statuses): array
    {
        $modules = array();
        foreach ($statuses as $key => $status) {
            if (!is_string($key) || sanitize_key($key) !== $key || !is_array($status)) {
                continue;
            }

            $dependencies = array();
            foreach ((array) ($status['dependencies'] ?? array()) as $dependency) {
                if (is_string($dependency) && sanitize_key($dependency) === $dependency) {
                    $dependencies[] = $dependency;
                }
            }

            $modules[$key] = array(
                'name' => self::text($status['name'] ?? $key),
                'product' => self::text($status['product'] ?? ''),
                'tier' => self::text($status['tier'] ?? ''),
                'dependencies' => array_values(array_unique($dependencies)),
                'enabled' => !empty($status['enabled']),
                'booted' => !empty($status['booted']),
                'status' => self::text($status['status'] ?? 'registered'),
                'issue' => self::text($status['issue'] ?? ''),
            );
        }

        ksort($modules);
        return $modules;
    }

    private static function constant(string $name): string
    {
        return defined($name) ? (string) constant($name) : 'Unavailable';
    }

    private static function text($value): string
    {
        return is_scalar($value) ? sanitize_text_field((string) $value) : '';
    }
}
