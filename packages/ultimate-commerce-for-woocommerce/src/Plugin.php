<?php

namespace BadOtter\UltimateCommerce;

use BadOtter\UltimateCommerce\Admin\DiagnosticsPage;
use BadOtter\UltimateCommerce\Compatibility\WooCommerceCompatibility;
use BadOtter\UltimateCommerce\Security\Capabilities;
use BadOtter\UltimateCommerce\Support\ModuleRegistry;
use BadOtter\UltimateCommerce\Support\OptionMigrator;

defined('ABSPATH') || exit;

final class Plugin
{
    private static ?ModuleRegistry $registry = null;

    public static function boot(): void
    {
        OptionMigrator::migrate();
        WooCommerceCompatibility::hooks();

        if (!class_exists('WooCommerce')) {
            add_action('admin_notices', array(__CLASS__, 'woocommerceMissingNotice'));
            return;
        }

        Capabilities::maybeInstall();

        $registry = new ModuleRegistry();
        $classes = require ULTIMATE_COMMERCE_DIR . 'config/modules.php';
        foreach ((array) apply_filters('uc_module_classes', $classes) as $class) {
            if (is_string($class) && class_exists($class)) {
                $registry->register(new $class());
            }
        }

        self::$registry = $registry;

        /**
         * Register Pro or third-party modules through the public module API.
         *
         * Extensions should call ModuleRegistry::register() with a Module implementation.
         */
        do_action('uc_register_modules', $registry);
        do_action('uc_modules_registered', $registry);

        $registry->boot();

        DiagnosticsPage::hooks();
        do_action('ultimate_commerce_loaded', $registry);
    }

    public static function activate(): void
    {
        OptionMigrator::migrate();
        Capabilities::install();
        update_option('uc_version', ULTIMATE_COMMERCE_VERSION, false);
        update_option('uc_schema_version', ULTIMATE_COMMERCE_SCHEMA_VERSION, false);
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
        echo '<div class="notice notice-error"><p>' . esc_html__('Ultimate Commerce for WooCommerce requires WooCommerce to be installed and active.', 'ultimate-commerce-for-woocommerce') . '</p></div>';
    }
}
