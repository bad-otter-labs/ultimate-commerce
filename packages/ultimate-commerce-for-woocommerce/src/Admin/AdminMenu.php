<?php

namespace BadOtter\UltimateCommerce\Admin;

use BadOtter\UltimateCommerce\Security\Capabilities;

defined('ABSPATH') || exit;

final class AdminMenu
{
    public const ROOT_SLUG = 'ultimate-commerce';
    public const DIAGNOSTICS_SLUG = 'ultimate-commerce-diagnostics';

    public static function hooks(): void
    {
        add_action('admin_menu', array(__CLASS__, 'register'), 20);
        add_action('admin_enqueue_scripts', array(__CLASS__, 'enqueueAssets'));
    }

    public static function register(): void
    {
        add_menu_page(
            __('Ultimate Commerce', 'ultimate-commerce-for-woocommerce'),
            __('Ultimate Commerce', 'ultimate-commerce-for-woocommerce'),
            Capabilities::VIEW_DIAGNOSTICS,
            self::ROOT_SLUG,
            array(OverviewPage::class, 'render'),
            'dashicons-store',
            56
        );

        add_submenu_page(
            self::ROOT_SLUG,
            __('Overview', 'ultimate-commerce-for-woocommerce'),
            __('Overview', 'ultimate-commerce-for-woocommerce'),
            Capabilities::VIEW_DIAGNOSTICS,
            self::ROOT_SLUG,
            array(OverviewPage::class, 'render')
        );

        add_submenu_page(
            self::ROOT_SLUG,
            __('Modules', 'ultimate-commerce-for-woocommerce'),
            __('Modules', 'ultimate-commerce-for-woocommerce'),
            Capabilities::MANAGE_SETTINGS,
            ModulesPage::SLUG,
            array(ModulesPage::class, 'render')
        );

        add_submenu_page(
            self::ROOT_SLUG,
            __('Settings', 'ultimate-commerce-for-woocommerce'),
            __('Settings', 'ultimate-commerce-for-woocommerce'),
            Capabilities::MANAGE_SETTINGS,
            SettingsPage::SLUG,
            array(SettingsPage::class, 'render')
        );

        add_submenu_page(
            self::ROOT_SLUG,
            __('Diagnostics', 'ultimate-commerce-for-woocommerce'),
            __('Diagnostics', 'ultimate-commerce-for-woocommerce'),
            Capabilities::VIEW_DIAGNOSTICS,
            self::DIAGNOSTICS_SLUG,
            array(DiagnosticsPage::class, 'render')
        );

        /**
         * Register supported Ultimate Commerce admin subpages.
         *
         * Extensions should attach submenu pages beneath the supplied parent slug
         * and enforce their own exact UC capability on every page callback.
         *
         * @param string $parentSlug Ultimate Commerce root menu slug.
         */
        do_action('ultimate_commerce_admin_menu', self::ROOT_SLUG);
    }

    public static function enqueueAssets(string $hookSuffix): void
    {
        $screens = array(
            'toplevel_page_' . self::ROOT_SLUG,
            self::ROOT_SLUG . '_page_' . ModulesPage::SLUG,
            self::ROOT_SLUG . '_page_' . SettingsPage::SLUG,
            self::ROOT_SLUG . '_page_' . self::DIAGNOSTICS_SLUG,
        );

        if (!in_array($hookSuffix, $screens, true)) {
            return;
        }

        wp_enqueue_style(
            'ultimate-commerce-admin',
            ULTIMATE_COMMERCE_URL . 'assets/css/admin.css',
            array(),
            ULTIMATE_COMMERCE_VERSION
        );
    }
}
