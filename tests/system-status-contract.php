<?php

declare(strict_types=1);

namespace Automattic\WooCommerce\Utilities {
    final class OrderUtil
    {
        public static function custom_orders_table_usage_is_enabled(): bool
        {
            return true;
        }
    }
}

namespace {
    define('ABSPATH', '/srv/private/example-store/');
    define('WC_VERSION', '10.1.2');
    define('WP_DEBUG', true);
    define('WP_DEBUG_LOG', '/srv/private/example-store/wp-content/debug.log');
    define('DISABLE_WP_CRON', false);
    define('WP_MEMORY_LIMIT', '256M');
    define('WP_MAX_MEMORY_LIMIT', '512M');
    define('ULTIMATE_COMMERCE_VERSION', '0.2.0');
    define('ULTIMATE_COMMERCE_SCHEMA_VERSION', '1');
    define('ULTIMATE_COMMERCE_MODULE_API_VERSION', '1.0.0');
    define('ULTIMATE_COMMERCE_SECURITY_API_VERSION', '1.0.0');
    define('ULTIMATE_COMMERCE_AUDIT_API_VERSION', '1.0.0');
    define('ULTIMATE_COMMERCE_PRIVACY_API_VERSION', '1.0.0');
    define('ULTIMATE_COMMERCE_EXECUTION_API_VERSION', '1.0.0');
    define('ULTIMATE_COMMERCE_STOREFRONT_API_VERSION', '1.0.0');
    define('ULTIMATE_COMMERCE_CART_API_VERSION', '1.0.0');
    define('ULTIMATE_COMMERCE_WISHLIST_API_VERSION', '1.0.0');

    $ucOptions = array('uc_delete_data_on_uninstall' => false);

    function get_option(string $name, $default = false)
    {
        global $ucOptions;
        return array_key_exists($name, $ucOptions) ? $ucOptions[$name] : $default;
    }

    function update_option(string $name, $value, $autoload = null): bool
    {
        global $ucOptions;
        unset($autoload);
        $ucOptions[$name] = $value;
        return true;
    }

    function get_bloginfo(string $show): string
    {
        return $show === 'version' ? '6.8.2' : '';
    }

    function is_multisite(): bool
    {
        return false;
    }

    function wp_using_ext_object_cache(): bool
    {
        return true;
    }

    function as_schedule_single_action(): void
    {
    }

    function sanitize_key(string $key): string
    {
        return preg_replace('/[^a-z0-9_\-]/', '', strtolower($key)) ?? '';
    }

    function sanitize_text_field(string $text): string
    {
        return trim(strip_tags($text));
    }

    function wp_json_encode($value, int $flags = 0): string
    {
        $json = json_encode($value, $flags);
        return is_string($json) ? $json : '{}';
    }

    function home_url(): string
    {
        return 'https://private.example.test';
    }

    function ucAssert(bool $condition, string $message): void
    {
        if (!$condition) {
            fwrite(STDERR, "FAIL: {$message}\n");
            exit(1);
        }
    }

    require_once __DIR__ . '/../packages/ultimate-commerce-for-woocommerce/src/Support/Settings.php';
    require_once __DIR__ . '/../packages/ultimate-commerce-for-woocommerce/src/Support/SystemStatus.php';

    use BadOtter\UltimateCommerce\Support\SystemStatus;

    $statuses = array(
        'cart' => array(
            'name' => 'Cart',
            'product' => 'ultimate-commerce-for-woocommerce',
            'tier' => 'free',
            'dependencies' => array(),
            'settings_schema' => array('secret' => 'super-secret-value'),
            'assets' => array('/srv/private/assets/cart.js'),
            'compatibility' => array('customer_email' => 'person@example.test'),
            'enabled' => true,
            'booted' => true,
            'status' => 'booted',
            'issue' => '',
        ),
        'pro_example' => array(
            'name' => 'Pro Example',
            'product' => 'ultimate-commerce-pro',
            'tier' => 'pro',
            'dependencies' => array('missing_core'),
            'enabled' => true,
            'booted' => false,
            'status' => 'blocked',
            'issue' => 'missing_dependency:missing_core',
        ),
    );

    $snapshot = SystemStatus::snapshot($statuses);
    $json = SystemStatus::supportJson($snapshot);

    ucAssert(($snapshot['schema'] ?? 0) === 1, 'Support snapshot schema must be explicit.');
    ucAssert(($snapshot['overall'] ?? '') === 'attention', 'Blocked modules must surface an attention state.');
    ucAssert(($snapshot['plugin']['version'] ?? '') === '0.2.0', 'Plugin version must be reported.');
    ucAssert(($snapshot['apis']['cart'] ?? '') === '1.0.0', 'Public API versions must be reported.');
    ucAssert(($snapshot['apis']['wishlist'] ?? '') === '1.0.0', 'Wishlist API version must be reported.');
    ucAssert(($snapshot['environment']['wordpress'] ?? '') === '6.8.2', 'WordPress version must be reported.');
    ucAssert(($snapshot['environment']['woocommerce'] ?? '') === '10.1.2', 'WooCommerce version must be reported.');
    ucAssert(($snapshot['environment']['wp_debug_log'] ?? null) === true, 'Debug-log state must be boolean only.');
    ucAssert(($snapshot['compatibility']['hpos_active'] ?? null) === true, 'HPOS runtime state must be reported.');
    ucAssert(($snapshot['compatibility']['cart_checkout_blocks_declared'] ?? null) === true, 'Blocks compatibility declaration must be reported.');
    ucAssert(($snapshot['module_counts']['registered'] ?? 0) === 2, 'Registered module count must be correct.');
    ucAssert(($snapshot['module_counts']['active'] ?? 0) === 1, 'Active module count must be correct.');
    ucAssert(($snapshot['module_counts']['blocked'] ?? 0) === 1, 'Blocked module count must be correct.');
    ucAssert(($snapshot['modules']['pro_example']['issue'] ?? '') === 'missing_dependency:missing_core', 'Dependency issue must be available to support.');
    ucAssert(!array_key_exists('settings_schema', $snapshot['modules']['cart']), 'Module settings schemas must not enter diagnostics output.');
    ucAssert(!array_key_exists('assets', $snapshot['modules']['cart']), 'Module asset metadata must not enter diagnostics output.');
    ucAssert(!array_key_exists('compatibility', $snapshot['modules']['cart']), 'Raw module compatibility metadata must not enter diagnostics output.');

    foreach (array(
        '/srv/private/example-store/',
        '/srv/private/example-store/wp-content/debug.log',
        '/srv/private/assets/cart.js',
        'super-secret-value',
        'person@example.test',
        'https://private.example.test',
    ) as $forbidden) {
        ucAssert(!str_contains($json, $forbidden), 'Support snapshot leaked forbidden private data: ' . $forbidden);
    }

    ucAssert(str_contains($json, 'missing_dependency:missing_core'), 'Support JSON must retain bounded module issue information.');
    ucAssert(str_contains($json, '"external_object_cache": true'), 'Support JSON must contain useful environment flags.');

    echo "Ultimate Commerce system-status contract passed.\n";
}
