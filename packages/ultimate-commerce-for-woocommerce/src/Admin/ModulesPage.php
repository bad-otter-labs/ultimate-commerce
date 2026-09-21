<?php

namespace BadOtter\UltimateCommerce\Admin;

use BadOtter\UltimateCommerce\Plugin;
use BadOtter\UltimateCommerce\Security\Capabilities;
use BadOtter\UltimateCommerce\Security\Csrf;
use BadOtter\UltimateCommerce\Support\Settings;

defined('ABSPATH') || exit;

final class ModulesPage
{
    public const SLUG = 'ultimate-commerce-modules';

    private const NONCE_FIELD = 'uc_modules_nonce';
    private const PURPOSE_SAVE = 'modules_save';

    public static function hooks(): void
    {
        add_action('admin_post_uc_modules_save', array(__CLASS__, 'save'));
    }

    public static function render(): void
    {
        self::requireCapability();

        $statuses = Plugin::registry()->statuses();
        $configured = Settings::moduleStates();
        uasort(
            $statuses,
            static function (array $left, array $right): int {
                return strcasecmp((string) ($left['name'] ?? ''), (string) ($right['name'] ?? ''));
            }
        );
        ?>
        <div class="wrap">
            <h1><?php echo esc_html__('Ultimate Commerce Modules', 'ultimate-commerce-for-woocommerce'); ?></h1>
            <p><?php echo esc_html__('Enable or disable registered Ultimate Commerce modules. WooCommerce remains the source of truth for products, stock, cart, totals, orders and payments.', 'ultimate-commerce-for-woocommerce'); ?></p>

            <?php self::renderNotice(self::noticeCode()); ?>

            <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
                <input type="hidden" name="action" value="uc_modules_save">
                <?php wp_nonce_field('uc_' . self::PURPOSE_SAVE, self::NONCE_FIELD); ?>

                <table class="widefat striped">
                    <thead>
                        <tr>
                            <th scope="col"><?php echo esc_html__('Module', 'ultimate-commerce-for-woocommerce'); ?></th>
                            <th scope="col"><?php echo esc_html__('Product / tier', 'ultimate-commerce-for-woocommerce'); ?></th>
                            <th scope="col"><?php echo esc_html__('Dependencies', 'ultimate-commerce-for-woocommerce'); ?></th>
                            <th scope="col"><?php echo esc_html__('Runtime state', 'ultimate-commerce-for-woocommerce'); ?></th>
                            <th scope="col"><?php echo esc_html__('Enabled', 'ultimate-commerce-for-woocommerce'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($statuses === array()) : ?>
                            <tr>
                                <td colspan="5"><?php echo esc_html__('No Ultimate Commerce modules are currently registered.', 'ultimate-commerce-for-woocommerce'); ?></td>
                            </tr>
                        <?php else : ?>
                            <?php foreach ($statuses as $key => $status) : ?>
                                <?php
                                $storedEnabled = !array_key_exists($key, $configured) ? true : (bool) $configured[$key];
                                $effectiveEnabled = !empty($status['enabled']);
                                $dependencies = array_values(array_filter((array) ($status['dependencies'] ?? array()), 'is_string'));
                                ?>
                                <tr>
                                    <th scope="row">
                                        <strong><?php echo esc_html((string) ($status['name'] ?? $key)); ?></strong><br>
                                        <code><?php echo esc_html($key); ?></code>
                                    </th>
                                    <td>
                                        <code><?php echo esc_html((string) ($status['product'] ?? '')); ?></code><br>
                                        <?php echo esc_html((string) ($status['tier'] ?? '')); ?>
                                    </td>
                                    <td>
                                        <?php if ($dependencies === array()) : ?>
                                            <?php echo esc_html__('None', 'ultimate-commerce-for-woocommerce'); ?>
                                        <?php else : ?>
                                            <?php foreach ($dependencies as $dependency) : ?>
                                                <code><?php echo esc_html($dependency); ?></code><br>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <strong><?php echo esc_html(self::statusLabel((string) ($status['status'] ?? 'registered'))); ?></strong>
                                        <?php if (!empty($status['issue'])) : ?>
                                            <p class="description"><code><?php echo esc_html((string) $status['issue']); ?></code></p>
                                        <?php endif; ?>
                                        <?php if ($storedEnabled !== $effectiveEnabled) : ?>
                                            <p class="description"><?php echo esc_html__('Effective state is currently overridden by code.', 'ultimate-commerce-for-woocommerce'); ?></p>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <label>
                                            <input
                                                type="checkbox"
                                                name="modules[<?php echo esc_attr($key); ?>]"
                                                value="1"
                                                <?php checked($storedEnabled); ?>
                                            >
                                            <?php echo esc_html__('Enabled', 'ultimate-commerce-for-woocommerce'); ?>
                                        </label>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>

                <p class="description"><?php echo esc_html__('Dependency failures are shown as blocked states. Saving does not silently change other module preferences.', 'ultimate-commerce-for-woocommerce'); ?></p>
                <?php submit_button(__('Save module settings', 'ultimate-commerce-for-woocommerce')); ?>
            </form>
        </div>
        <?php
    }

    public static function save(): void
    {
        self::authorizeRequest();

        // phpcs:disable WordPress.Security.NonceVerification.Missing, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- authorizeRequest() verifies the nonce first; only registry-owned array keys are inspected and submitted values are ignored.
        $posted = isset($_POST['modules']) && is_array($_POST['modules'])
            ? wp_unslash($_POST['modules'])
            : array();
        // phpcs:enable WordPress.Security.NonceVerification.Missing, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
        $states = array();
        foreach (array_keys(Plugin::registry()->all()) as $key) {
            $states[$key] = array_key_exists($key, $posted);
        }

        Settings::updateModuleStates($states);
        self::redirect('saved');
    }

    private static function authorizeRequest(): void
    {
        self::requireCapability();

        // phpcs:disable WordPress.Security.NonceVerification.Missing -- This reads the nonce field solely so the shared Csrf::require() verifier can validate it below.
        $nonce = isset($_POST[self::NONCE_FIELD]) && is_scalar($_POST[self::NONCE_FIELD])
            ? sanitize_text_field((string) wp_unslash($_POST[self::NONCE_FIELD]))
            : '';
        // phpcs:enable WordPress.Security.NonceVerification.Missing
        $verified = Csrf::require($nonce, self::PURPOSE_SAVE);
        if ($verified instanceof \WP_Error) {
            wp_die(
                esc_html($verified->get_error_message()),
                esc_html__('Request rejected', 'ultimate-commerce-for-woocommerce'),
                array('response' => 403)
            );
        }
    }

    private static function requireCapability(): void
    {
        if (!current_user_can(Capabilities::MANAGE_SETTINGS)) {
            wp_die(
                esc_html__('You do not have permission to manage Ultimate Commerce modules.', 'ultimate-commerce-for-woocommerce'),
                esc_html__('Access denied', 'ultimate-commerce-for-woocommerce'),
                array('response' => 403)
            );
        }
    }

    private static function redirect(string $status): void
    {
        $url = add_query_arg(
            array(
                'page' => self::SLUG,
                'uc_modules_status' => sanitize_key($status),
            ),
            admin_url('admin.php')
        );
        wp_safe_redirect($url);
        exit;
    }

    private static function noticeCode(): string
    {
        // phpcs:disable WordPress.Security.NonceVerification.Recommended -- Read-only, sanitized admin notice state; it does not authorize or mutate data.
        if (!isset($_GET['uc_modules_status']) || !is_scalar($_GET['uc_modules_status'])) {
            return '';
        }
        $code = sanitize_key((string) wp_unslash($_GET['uc_modules_status']));
        // phpcs:enable WordPress.Security.NonceVerification.Recommended
        return $code;
    }

    private static function renderNotice(string $code): void
    {
        if ($code !== 'saved') {
            return;
        }
        echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__('Module settings saved.', 'ultimate-commerce-for-woocommerce') . '</p></div>';
    }

    private static function statusLabel(string $status): string
    {
        switch ($status) {
            case 'booted':
                return __('Active', 'ultimate-commerce-for-woocommerce');
            case 'disabled':
                return __('Disabled', 'ultimate-commerce-for-woocommerce');
            case 'blocked':
                return __('Blocked', 'ultimate-commerce-for-woocommerce');
            case 'booting':
                return __('Starting', 'ultimate-commerce-for-woocommerce');
            default:
                return __('Registered', 'ultimate-commerce-for-woocommerce');
        }
    }
}
