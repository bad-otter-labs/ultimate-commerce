<?php

namespace BadOtter\UltimateCommerce;

use BadOtter\UltimateCommerce\Admin\DiagnosticsPage;
use BadOtter\UltimateCommerce\Compatibility\WooCommerceCompatibility;
use BadOtter\UltimateCommerce\Support\ModuleRegistry;
use BadOtter\UltimateCommerce\Updates\ManagedUpdates;

defined('ABSPATH') || exit;

final class Plugin
{
    private static ?ModuleRegistry $registry = null;

    public static function boot(): void
    {
        ManagedUpdates::hooks();
        WooCommerceCompatibility::hooks();

        if (!class_exists('WooCommerce')) {
            add_action('admin_notices', array(__CLASS__, 'woocommerceMissingNotice'));
            return;
        }

        $registry = new ModuleRegistry();
        $classes = require ULTIMATE_COMMERCE_DIR . 'config/modules.php';
        foreach ((array) apply_filters('uc_module_classes', $classes) as $class) {
            if (is_string($class) && class_exists($class)) {
                $registry->register(new $class());
            }
        }
        $registry->boot();
        self::$registry = $registry;

        DiagnosticsPage::hooks();
        do_action('ultimate_commerce_loaded', $registry);
    }

    public static function activate(): void
    {
        update_option('ultimate_commerce_version', ULTIMATE_COMMERCE_VERSION, false);
        update_option('ultimate_commerce_schema_version', ULTIMATE_COMMERCE_SCHEMA_VERSION, false);
        ManagedUpdates::flush();
    }

    public static function registry(): ModuleRegistry
    {
        if (!self::$registry) {
            self::$registry = new ModuleRegistry();
        }
        return self::$registry;
    }

    public static function woocommerceMissingNotice(): void
    {
        if (!current_user_can('activate_plugins')) {
            return;
        }
        echo '<div class="notice notice-error"><p>' . esc_html__('Ultimate Commerce requires WooCommerce to be installed and active.', 'ultimate-commerce') . '</p></div>';
    }
}
