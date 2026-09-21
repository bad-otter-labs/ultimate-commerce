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
        <div class="wrap uc-admin uc-admin--overview">
            <h1 class="uc-admin__title"><?php echo esc_html__('Ultimate Commerce', 'ultimate-commerce-for-woocommerce'); ?></h1>
            <p class="uc-admin__lede"><?php echo esc_html__('Commerce experience and retail operations foundations for WooCommerce.', 'ultimate-commerce-for-woocommerce'); ?></p>

            <h2 class="uc-section-title"><?php echo esc_html__('Overview', 'ultimate-commerce-for-woocommerce'); ?></h2>
            <table class="widefat striped uc-summary-grid">
                <tbody>
                    <tr><th><?php echo esc_html__('Version', 'ultimate-commerce-for-woocommerce'); ?></th><td><?php echo esc_html(ULTIMATE_COMMERCE_VERSION); ?></td></tr>
                    <tr><th><?php echo esc_html__('Registered modules', 'ultimate-commerce-for-woocommerce'); ?></th><td><?php echo esc_html((string) $total); ?></td></tr>
                    <tr><th><?php echo esc_html__('Active modules', 'ultimate-commerce-for-woocommerce'); ?></th><td><?php echo esc_html((string) $booted); ?></td></tr>
                    <tr><th><?php echo esc_html__('Blocked modules', 'ultimate-commerce-for-woocommerce'); ?></th><td><?php echo esc_html((string) $blocked); ?></td></tr>
                    <tr><th><?php echo esc_html__('Disabled modules', 'ultimate-commerce-for-woocommerce'); ?></th><td><?php echo esc_html((string) $disabled); ?></td></tr>
                </tbody>
            </table>

            <p class="uc-actions">
                <a class="button button-secondary" href="<?php echo esc_url($diagnosticsUrl); ?>">
                    <?php echo esc_html__('View diagnostics', 'ultimate-commerce-for-woocommerce'); ?>
                </a>
                <a class="button button-secondary" href="<?php echo esc_url('https://github.com/bad-otter-labs/ultimate-commerce/tree/main/docs'); ?>" target="_blank" rel="noopener noreferrer">
                    <?php echo esc_html__('Documentation', 'ultimate-commerce-for-woocommerce'); ?>
                </a>
            </p>

            <div class="uc-section-gap" aria-hidden="true"></div>

            <h2 class="uc-pro-heading"><?php echo esc_html__('Ultimate Commerce Pro', 'ultimate-commerce-for-woocommerce'); ?></h2>
            <p class="uc-pro-copy">
                <?php echo esc_html__('Ultimate Commerce Pro is an optional paid companion for advanced conversion, merchandising, customer and retail-operations modules. Ultimate Commerce Free remains usable without Pro, a Bad Otter account or a hosted Bad Otter service.', 'ultimate-commerce-for-woocommerce'); ?>
            </p>

            <?php
            /**
             * Render extension-owned content on the Ultimate Commerce overview.
             *
             * @param array<string, array<string, mixed>> $statuses Module statuses.
             */
            do_action('ultimate_commerce_admin_overview', $statuses);
            ?>
        </div>
        <?php
    }
}
