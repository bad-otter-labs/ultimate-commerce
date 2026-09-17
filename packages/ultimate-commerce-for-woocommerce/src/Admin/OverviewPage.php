<?php

namespace BadOtter\UltimateCommerce\Admin;

use BadOtter\UltimateCommerce\Plugin;
use BadOtter\UltimateCommerce\Security\Capabilities;

defined('ABSPATH') || exit;

final class OverviewPage
{
    public static function render(): void
    {
        if (!current_user_can(Capabilities::VIEW_DIAGNOSTICS)) {
            wp_die(esc_html__('You do not have permission to view this page.', 'ultimate-commerce-for-woocommerce'));
        }

        $statuses = Plugin::registry()->statuses();
        $total = count($statuses);
        $booted = count(array_filter(
            $statuses,
            static fn(array $status): bool => !empty($status['booted'])
        ));
        $blocked = count(array_filter(
            $statuses,
            static fn(array $status): bool => ($status['status'] ?? '') === 'blocked'
        ));
        $disabled = count(array_filter(
            $statuses,
            static fn(array $status): bool => ($status['status'] ?? '') === 'disabled'
        ));
        $diagnosticsUrl = admin_url('admin.php?page=' . AdminMenu::DIAGNOSTICS_SLUG);
        ?>
        <div class="wrap">
            <h1><?php echo esc_html__('Ultimate Commerce', 'ultimate-commerce-for-woocommerce'); ?></h1>
            <p><?php echo esc_html__('Commerce experience and retail operations foundations for WooCommerce.', 'ultimate-commerce-for-woocommerce'); ?></p>

            <h2><?php echo esc_html__('Overview', 'ultimate-commerce-for-woocommerce'); ?></h2>
            <table class="widefat striped" style="max-width:900px">
                <tbody>
                    <tr><th><?php echo esc_html__('Version', 'ultimate-commerce-for-woocommerce'); ?></th><td><?php echo esc_html(ULTIMATE_COMMERCE_VERSION); ?></td></tr>
                    <tr><th><?php echo esc_html__('Registered modules', 'ultimate-commerce-for-woocommerce'); ?></th><td><?php echo esc_html((string) $total); ?></td></tr>
                    <tr><th><?php echo esc_html__('Active modules', 'ultimate-commerce-for-woocommerce'); ?></th><td><?php echo esc_html((string) $booted); ?></td></tr>
                    <tr><th><?php echo esc_html__('Blocked modules', 'ultimate-commerce-for-woocommerce'); ?></th><td><?php echo esc_html((string) $blocked); ?></td></tr>
                    <tr><th><?php echo esc_html__('Disabled modules', 'ultimate-commerce-for-woocommerce'); ?></th><td><?php echo esc_html((string) $disabled); ?></td></tr>
                </tbody>
            </table>

            <p>
                <a class="button button-secondary" href="<?php echo esc_url($diagnosticsUrl); ?>">
                    <?php echo esc_html__('View diagnostics', 'ultimate-commerce-for-woocommerce'); ?>
                </a>
            </p>

            <?php
            /**
             * Render extension-owned content on the Ultimate Commerce overview.
             *
             * @param array<string, array<string, mixed>> $statuses Module statuses.
             */
            do_action('uc_admin_overview', $statuses);
            ?>
        </div>
        <?php
    }
}
