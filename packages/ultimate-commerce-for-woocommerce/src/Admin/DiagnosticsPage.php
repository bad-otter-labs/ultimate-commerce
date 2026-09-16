<?php

namespace BadOtter\UltimateCommerce\Admin;

use BadOtter\UltimateCommerce\Plugin;

defined('ABSPATH') || exit;

final class DiagnosticsPage
{
    public static function hooks(): void
    {
        add_action('admin_menu', array(__CLASS__, 'menu'));
    }

    public static function menu(): void
    {
        add_submenu_page(
            'woocommerce',
            __('Ultimate Commerce', 'ultimate-commerce-for-woocommerce'),
            __('Ultimate Commerce', 'ultimate-commerce-for-woocommerce'),
            'manage_woocommerce',
            'ultimate-commerce',
            array(__CLASS__, 'render')
        );
    }

    public static function render(): void
    {
        if (!current_user_can('manage_woocommerce')) {
            wp_die(esc_html__('You do not have permission to view this page.', 'ultimate-commerce-for-woocommerce'));
        }

        $registry = Plugin::registry();
        $hpos = class_exists(\Automattic\WooCommerce\Utilities\OrderUtil::class)
            ? \Automattic\WooCommerce\Utilities\OrderUtil::custom_orders_table_usage_is_enabled()
            : false;
        ?>
        <div class="wrap">
            <h1><?php echo esc_html__('Ultimate Commerce', 'ultimate-commerce-for-woocommerce'); ?></h1>
            <p><?php echo esc_html__('Reusable WooCommerce enhancement platform by Bad Otter Labs.', 'ultimate-commerce-for-woocommerce'); ?></p>
            <table class="widefat striped" style="max-width:900px">
                <tbody>
                    <tr><th><?php echo esc_html__('Version', 'ultimate-commerce-for-woocommerce'); ?></th><td><?php echo esc_html(ULTIMATE_COMMERCE_VERSION); ?></td></tr>
                    <tr><th><?php echo esc_html__('WooCommerce', 'ultimate-commerce-for-woocommerce'); ?></th><td><?php echo esc_html(defined('WC_VERSION') ? WC_VERSION : 'Unavailable'); ?></td></tr>
                    <tr><th><?php echo esc_html__('HPOS active', 'ultimate-commerce-for-woocommerce'); ?></th><td><?php echo esc_html($hpos ? 'Yes' : 'No'); ?></td></tr>
                    <tr><th><?php echo esc_html__('Schema', 'ultimate-commerce-for-woocommerce'); ?></th><td><?php echo esc_html(ULTIMATE_COMMERCE_SCHEMA_VERSION); ?></td></tr>
                </tbody>
            </table>
            <h2><?php echo esc_html__('Modules', 'ultimate-commerce-for-woocommerce'); ?></h2>
            <table class="widefat striped" style="max-width:900px">
                <thead><tr><th><?php echo esc_html__('Module', 'ultimate-commerce-for-woocommerce'); ?></th><th><?php echo esc_html__('State', 'ultimate-commerce-for-woocommerce'); ?></th></tr></thead>
                <tbody>
                <?php foreach ($registry->states() as $key => $active) : ?>
                    <tr><td><code><?php echo esc_html($key); ?></code></td><td><?php echo esc_html($active ? 'Enabled' : 'Disabled'); ?></td></tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php
    }
}
