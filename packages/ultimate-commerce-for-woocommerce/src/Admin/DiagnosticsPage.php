<?php

namespace BadOtter\UltimateCommerce\Admin;

use BadOtter\UltimateCommerce\Plugin;
use BadOtter\UltimateCommerce\Security\Capabilities;
use BadOtter\UltimateCommerce\Support\SystemStatus;

defined('ABSPATH') || exit;

final class DiagnosticsPage
{
    public static function render(): void
    {
        if (!current_user_can(Capabilities::VIEW_DIAGNOSTICS)) {
            wp_die(esc_html__('You do not have permission to view this page.', 'ultimate-commerce-for-woocommerce'));
        }

        $snapshot = SystemStatus::snapshot(Plugin::registry()->statuses());
        $environment = (array) ($snapshot['environment'] ?? array());
        $compatibility = (array) ($snapshot['compatibility'] ?? array());
        $apis = (array) ($snapshot['apis'] ?? array());
        $counts = (array) ($snapshot['module_counts'] ?? array());
        $modules = (array) ($snapshot['modules'] ?? array());
        $supportJson = SystemStatus::supportJson($snapshot);
        ?>
        <div class="wrap uc-admin uc-admin--diagnostics">
            <h1 class="uc-admin__title"><?php echo esc_html__('Ultimate Commerce Diagnostics', 'ultimate-commerce-for-woocommerce'); ?></h1>
            <p class="uc-admin__lede"><?php echo esc_html__('Read-only system state for Ultimate Commerce, WooCommerce compatibility and registered modules.', 'ultimate-commerce-for-woocommerce'); ?></p>

            <h2 class="uc-diagnostics-title"><?php echo esc_html__('System summary', 'ultimate-commerce-for-woocommerce'); ?></h2>
            <table class="widefat striped uc-data-table">
                <tbody>
                    <tr>
                        <th scope="row"><?php echo esc_html__('Overall status', 'ultimate-commerce-for-woocommerce'); ?></th>
                        <td><span class="uc-status uc-status--<?php echo esc_attr(($snapshot['overall'] ?? 'attention') === 'healthy' ? 'healthy' : 'attention'); ?>"><?php echo esc_html(self::overallLabel((string) ($snapshot['overall'] ?? 'attention'))); ?></span></td>
                    </tr>
                    <tr>
                        <th scope="row"><?php echo esc_html__('Ultimate Commerce version', 'ultimate-commerce-for-woocommerce'); ?></th>
                        <td><code><?php echo esc_html((string) ($snapshot['plugin']['version'] ?? 'Unavailable')); ?></code></td>
                    </tr>
                    <tr>
                        <th scope="row"><?php echo esc_html__('Schema version', 'ultimate-commerce-for-woocommerce'); ?></th>
                        <td><code><?php echo esc_html((string) ($snapshot['plugin']['schema'] ?? 'Unavailable')); ?></code></td>
                    </tr>
                    <tr>
                        <th scope="row"><?php echo esc_html__('Development build', 'ultimate-commerce-for-woocommerce'); ?></th>
                        <td><?php echo esc_html(self::yesNo(!empty($snapshot['plugin']['development_build']))); ?></td>
                    </tr>
                    <tr>
                        <th scope="row"><?php echo esc_html__('Registered modules', 'ultimate-commerce-for-woocommerce'); ?></th>
                        <td><?php echo esc_html((string) ($counts['registered'] ?? 0)); ?></td>
                    </tr>
                    <tr>
                        <th scope="row"><?php echo esc_html__('Active modules', 'ultimate-commerce-for-woocommerce'); ?></th>
                        <td><?php echo esc_html((string) ($counts['active'] ?? 0)); ?></td>
                    </tr>
                    <tr>
                        <th scope="row"><?php echo esc_html__('Blocked modules', 'ultimate-commerce-for-woocommerce'); ?></th>
                        <td><?php echo esc_html((string) ($counts['blocked'] ?? 0)); ?></td>
                    </tr>
                </tbody>
            </table>

            <h2 class="uc-diagnostics-title"><?php echo esc_html__('Environment', 'ultimate-commerce-for-woocommerce'); ?></h2>
            <table class="widefat striped uc-data-table">
                <tbody>
                    <tr><th scope="row"><?php echo esc_html__('WordPress', 'ultimate-commerce-for-woocommerce'); ?></th><td><code><?php echo esc_html((string) ($environment['wordpress'] ?? 'Unavailable')); ?></code></td></tr>
                    <tr><th scope="row"><?php echo esc_html__('WooCommerce', 'ultimate-commerce-for-woocommerce'); ?></th><td><code><?php echo esc_html((string) ($environment['woocommerce'] ?? 'Unavailable')); ?></code></td></tr>
                    <tr><th scope="row"><?php echo esc_html__('PHP', 'ultimate-commerce-for-woocommerce'); ?></th><td><code><?php echo esc_html((string) ($environment['php'] ?? 'Unavailable')); ?></code></td></tr>
                    <tr><th scope="row"><?php echo esc_html__('WordPress memory limit', 'ultimate-commerce-for-woocommerce'); ?></th><td><code><?php echo esc_html((string) ($environment['memory_limit'] ?? '')); ?></code></td></tr>
                    <tr><th scope="row"><?php echo esc_html__('WordPress max memory limit', 'ultimate-commerce-for-woocommerce'); ?></th><td><code><?php echo esc_html((string) ($environment['max_memory_limit'] ?? '')); ?></code></td></tr>
                    <tr><th scope="row"><?php echo esc_html__('Multisite', 'ultimate-commerce-for-woocommerce'); ?></th><td><?php echo esc_html(self::yesNo(!empty($environment['multisite']))); ?></td></tr>
                    <tr><th scope="row"><?php echo esc_html__('External object cache', 'ultimate-commerce-for-woocommerce'); ?></th><td><?php echo esc_html(self::yesNo(!empty($environment['external_object_cache']))); ?></td></tr>
                    <tr><th scope="row"><?php echo esc_html__('WP_DEBUG', 'ultimate-commerce-for-woocommerce'); ?></th><td><?php echo esc_html(self::yesNo(!empty($environment['wp_debug']))); ?></td></tr>
                    <tr><th scope="row"><?php echo esc_html__('Debug logging enabled', 'ultimate-commerce-for-woocommerce'); ?></th><td><?php echo esc_html(self::yesNo(!empty($environment['wp_debug_log']))); ?></td></tr>
                    <tr><th scope="row"><?php echo esc_html__('WP-Cron disabled', 'ultimate-commerce-for-woocommerce'); ?></th><td><?php echo esc_html(self::yesNo(!empty($environment['wp_cron_disabled']))); ?></td></tr>
                </tbody>
            </table>

            <h2 class="uc-diagnostics-title"><?php echo esc_html__('WooCommerce compatibility', 'ultimate-commerce-for-woocommerce'); ?></h2>
            <table class="widefat striped uc-data-table">
                <tbody>
                    <tr><th scope="row"><?php echo esc_html__('HPOS compatibility declared', 'ultimate-commerce-for-woocommerce'); ?></th><td><?php echo esc_html(self::yesNo(!empty($compatibility['hpos_declared']))); ?></td></tr>
                    <tr><th scope="row"><?php echo esc_html__('HPOS active', 'ultimate-commerce-for-woocommerce'); ?></th><td><?php echo esc_html(self::yesNo(!empty($compatibility['hpos_active']))); ?></td></tr>
                    <tr><th scope="row"><?php echo esc_html__('Cart/Checkout Blocks compatibility declared', 'ultimate-commerce-for-woocommerce'); ?></th><td><?php echo esc_html(self::yesNo(!empty($compatibility['cart_checkout_blocks_declared']))); ?></td></tr>
                    <tr><th scope="row"><?php echo esc_html__('Action Scheduler available', 'ultimate-commerce-for-woocommerce'); ?></th><td><?php echo esc_html(self::yesNo(!empty($compatibility['action_scheduler_available']))); ?></td></tr>
                </tbody>
            </table>

            <h2 class="uc-diagnostics-title"><?php echo esc_html__('Public API versions', 'ultimate-commerce-for-woocommerce'); ?></h2>
            <table class="widefat striped uc-data-table">
                <thead><tr><th scope="col"><?php echo esc_html__('API', 'ultimate-commerce-for-woocommerce'); ?></th><th scope="col"><?php echo esc_html__('Version', 'ultimate-commerce-for-woocommerce'); ?></th></tr></thead>
                <tbody>
                    <?php foreach ($apis as $api => $version) : ?>
                        <tr><th scope="row"><code><?php echo esc_html((string) $api); ?></code></th><td><code><?php echo esc_html((string) $version); ?></code></td></tr>
                    <?php endforeach; ?>
                </tbody>
            </table>

            <h2 class="uc-diagnostics-title"><?php echo esc_html__('Modules', 'ultimate-commerce-for-woocommerce'); ?></h2>
            <table class="widefat striped uc-data-table uc-diagnostics-modules">
                <thead>
                    <tr>
                        <th scope="col"><?php echo esc_html__('Module', 'ultimate-commerce-for-woocommerce'); ?></th>
                        <th scope="col"><?php echo esc_html__('Product / tier', 'ultimate-commerce-for-woocommerce'); ?></th>
                        <th scope="col"><?php echo esc_html__('Dependencies', 'ultimate-commerce-for-woocommerce'); ?></th>
                        <th scope="col"><?php echo esc_html__('State', 'ultimate-commerce-for-woocommerce'); ?></th>
                        <th scope="col"><?php echo esc_html__('Issue', 'ultimate-commerce-for-woocommerce'); ?></th>
                    </tr>
                </thead>
                <tbody>
                <?php if ($modules === array()) : ?>
                    <tr><td colspan="5"><?php echo esc_html__('No Ultimate Commerce modules are registered.', 'ultimate-commerce-for-woocommerce'); ?></td></tr>
                <?php else : ?>
                    <?php foreach ($modules as $key => $module) : ?>
                        <?php $dependencies = array_values(array_filter((array) ($module['dependencies'] ?? array()), 'is_string')); ?>
                        <tr>
                            <th scope="row"><?php echo esc_html((string) ($module['name'] ?? $key)); ?> <code><?php echo esc_html((string) $key); ?></code></th>
                            <td><code><?php echo esc_html((string) ($module['product'] ?? '')); ?></code><br><?php echo esc_html((string) ($module['tier'] ?? '')); ?></td>
                            <td><?php echo $dependencies === array() ? esc_html__('None', 'ultimate-commerce-for-woocommerce') : esc_html(implode(', ', $dependencies)); ?></td>
                            <td><span class="uc-status uc-status--<?php echo esc_attr(sanitize_html_class((string) ($module['status'] ?? 'registered'))); ?>"><?php echo esc_html((string) ($module['status'] ?? 'registered')); ?></span></td>
                            <td><code><?php echo esc_html((string) ($module['issue'] ?? '')); ?></code></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
                </tbody>
            </table>

            <h2 class="uc-support-title"><?php echo esc_html__('Support snapshot', 'ultimate-commerce-for-woocommerce'); ?></h2>
            <p><?php echo esc_html__('Copy this JSON when asking for support. It excludes site URLs, filesystem paths, credentials, secrets, customer/order data and module settings payloads.', 'ultimate-commerce-for-woocommerce'); ?></p>
            <label for="uc-support-snapshot" class="screen-reader-text"><?php echo esc_html__('Ultimate Commerce support snapshot', 'ultimate-commerce-for-woocommerce'); ?></label>
            <textarea id="uc-support-snapshot" class="large-text code uc-support-snapshot" rows="24" readonly><?php echo esc_textarea($supportJson); ?></textarea>
        </div>
        <?php
    }

    private static function yesNo(bool $value): string
    {
        return $value
            ? __('Yes', 'ultimate-commerce-for-woocommerce')
            : __('No', 'ultimate-commerce-for-woocommerce');
    }

    private static function overallLabel(string $status): string
    {
        return $status === 'healthy'
            ? __('Healthy', 'ultimate-commerce-for-woocommerce')
            : __('Attention needed', 'ultimate-commerce-for-woocommerce');
    }
}
