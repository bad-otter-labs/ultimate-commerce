<?php

namespace BadOtter\UltimateCommerce\Admin;

use BadOtter\UltimateCommerce\Security\Capabilities;
use BadOtter\UltimateCommerce\Support\Settings;
use BadOtter\UltimateCommerce\Support\SettingsTransfer;

defined('ABSPATH') || exit;

final class SettingsPage
{
    public const SLUG = 'ultimate-commerce-settings';

    private const NONCE_FIELD = 'ulticofo_settings_transfer_nonce';
    private const PURPOSE_EXPORT = 'settings_export';
    private const PURPOSE_IMPORT = 'settings_import';
    private const PURPOSE_RETENTION = 'settings_retention';

    public static function hooks(): void
    {
        add_action('admin_post_ulticofo_settings_export', array(__CLASS__, 'export'));
        add_action('admin_post_ulticofo_settings_import', array(__CLASS__, 'import'));
        add_action('admin_post_ulticofo_settings_retention', array(__CLASS__, 'saveRetention'));
    }

    public static function render(): void
    {
        self::requireCapability();
        $deleteDataOnUninstall = Settings::deleteDataOnUninstall();
        ?>
        <div class="wrap uc-admin uc-admin--settings">
            <h1 class="uc-admin__title"><?php echo esc_html__('Ultimate Commerce Settings', 'ultimate-commerce-for-woocommerce'); ?></h1>
            <p class="uc-admin__lede"><?php echo esc_html__('Export or import portable Ultimate Commerce merchant settings. Runtime metadata, secrets and caches are never part of this file.', 'ultimate-commerce-for-woocommerce'); ?></p>

            <?php self::renderNotice(self::noticeCode()); ?>

            <section class="uc-card uc-settings-card"><h2 class="uc-card__title"><?php echo esc_html__('Export settings', 'ultimate-commerce-for-woocommerce'); ?></h2>
            <p><?php echo esc_html__('Download a JSON file containing supported merchant configuration. The current format includes stored module preferences, including temporarily unavailable extension modules.', 'ultimate-commerce-for-woocommerce'); ?></p>
            <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
                <input type="hidden" name="action" value="ulticofo_settings_export">
                <?php wp_nonce_field('ulticofo_' . self::PURPOSE_EXPORT, self::NONCE_FIELD); ?>
                <?php submit_button(__('Download settings file', 'ultimate-commerce-for-woocommerce'), 'secondary', 'submit', false); ?>
            </form></section>

            <div class="uc-section-gap" aria-hidden="true"></div>

            <section class="uc-card uc-settings-card"><h2 class="uc-card__title"><?php echo esc_html__('Import settings', 'ultimate-commerce-for-woocommerce'); ?></h2>
            <p><?php echo esc_html__('Import a JSON file previously exported by Ultimate Commerce. Declared module preferences are merged with existing settings, so omitted extension preferences are preserved.', 'ultimate-commerce-for-woocommerce'); ?></p>
            <form class="uc-settings-form" method="post" enctype="multipart/form-data" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
                <input type="hidden" name="action" value="ulticofo_settings_import">
                <?php wp_nonce_field('ulticofo_' . self::PURPOSE_IMPORT, self::NONCE_FIELD); ?>
                <p>
                    <label for="uc-settings-file"><strong><?php echo esc_html__('Settings JSON file', 'ultimate-commerce-for-woocommerce'); ?></strong></label><br>
                    <input class="uc-file-input" id="uc-settings-file" name="settings_file" type="file" accept=".json,application/json" required>
                </p>
                <p class="description">
                    <?php
                    echo esc_html(
                        sprintf(
                            /* translators: %s: Maximum settings file size. */
                            __('Maximum file size: %s KB. Imported settings take effect on the next request.', 'ultimate-commerce-for-woocommerce'),
                            (string) (SettingsTransfer::MAX_BYTES / 1024)
                        )
                    );
                    ?>
                </p>
                <?php submit_button(__('Import settings', 'ultimate-commerce-for-woocommerce'), 'primary', 'submit', false); ?>
            </form></section>

            <div class="uc-section-gap" aria-hidden="true"></div>

            <section class="uc-card uc-settings-card uc-settings-card--danger"><h2 class="uc-card__title"><?php echo esc_html__('Data retention', 'ultimate-commerce-for-woocommerce'); ?></h2>
            <p><?php echo esc_html__('Ultimate Commerce keeps merchant configuration by default when the plugin is deleted, making a later reinstall recoverable. Short-lived runtime locks, replay records, idempotency records and rate-limit transients are always removed.', 'ultimate-commerce-for-woocommerce'); ?></p>
            <form class="uc-settings-form" method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
                <input type="hidden" name="action" value="ulticofo_settings_retention">
                <?php wp_nonce_field('ulticofo_' . self::PURPOSE_RETENTION, self::NONCE_FIELD); ?>
                <p>
                    <label class="uc-toggle uc-toggle--danger">
                        <input type="checkbox" name="delete_data_on_uninstall" value="1" <?php checked($deleteDataOnUninstall); ?>>
                        <strong><?php echo esc_html__('Delete Ultimate Commerce data when the plugin is deleted', 'ultimate-commerce-for-woocommerce'); ?></strong>
                    </label>
                </p>
                <p class="description"><?php echo esc_html__('When enabled, deletion also removes stored module preferences and Ultimate Commerce encrypted secrets. WooCommerce products, stock, carts, orders, payments and other WooCommerce-owned data are never deleted by this cleanup.', 'ultimate-commerce-for-woocommerce'); ?></p>
                <p class="description"><?php echo esc_html__('This destructive local preference is intentionally excluded from settings export/import.', 'ultimate-commerce-for-woocommerce'); ?></p>
                <?php submit_button(__('Save data retention', 'ultimate-commerce-for-woocommerce'), 'secondary', 'submit', false); ?>
            </form></section>
        </div>
        <?php
    }

    public static function export(): void
    {
        self::requireCapability();
        check_admin_referer('ulticofo_' . self::PURPOSE_EXPORT, self::NONCE_FIELD);

        $json = SettingsTransfer::exportJson();
        if ($json instanceof \WP_Error) {
            wp_die(
                esc_html($json->get_error_message()),
                esc_html__('Settings export failed', 'ultimate-commerce-for-woocommerce'),
                array('response' => 400)
            );
        }

        nocache_headers();
        send_nosniff_header();
        header('Content-Type: application/json; charset=utf-8');
        header('Content-Disposition: attachment; filename="ultimate-commerce-settings.json"');
        echo $json; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Validated JSON download payload.
        exit;
    }

    public static function import(): void
    {
        self::requireCapability();
        check_admin_referer('ulticofo_' . self::PURPOSE_IMPORT, self::NONCE_FIELD);

        // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Upload metadata is validated field-by-field by uploadedJson().
        $file = isset($_FILES['settings_file']) && is_array($_FILES['settings_file']) ? $_FILES['settings_file'] : null;
        $json = self::uploadedJson($file);
        if ($json instanceof \WP_Error) {
            self::redirect('invalid');
        }

        $result = SettingsTransfer::importJson($json);
        if ($result instanceof \WP_Error) {
            self::redirect('invalid');
        }

        self::redirect('imported');
    }

    public static function saveRetention(): void
    {
        self::requireCapability();
        check_admin_referer('ulticofo_' . self::PURPOSE_RETENTION, self::NONCE_FIELD);

        $enabled = isset($_POST['delete_data_on_uninstall'])
            && is_scalar($_POST['delete_data_on_uninstall'])
            && sanitize_text_field((string) wp_unslash($_POST['delete_data_on_uninstall'])) === '1';

        Settings::updateDeleteDataOnUninstall($enabled);
        self::redirect('retention-saved');
    }

    /** @param array<string, mixed>|null $file
     *  @return string|\WP_Error
     */
    private static function uploadedJson(?array $file)
    {
        if ($file === null) {
            return new \WP_Error('uc_settings_upload_missing', __('Choose an Ultimate Commerce settings file to import.', 'ultimate-commerce-for-woocommerce'));
        }
        $error = isset($file['error']) ? (int) $file['error'] : UPLOAD_ERR_NO_FILE;
        $size = isset($file['size']) ? (int) $file['size'] : 0;
        $tmpName = isset($file['tmp_name']) && is_string($file['tmp_name']) ? $file['tmp_name'] : '';

        if ($error !== UPLOAD_ERR_OK || $tmpName === '' || $size > SettingsTransfer::MAX_BYTES || !is_uploaded_file($tmpName)) {
            return new \WP_Error('uc_settings_upload_invalid', __('The uploaded settings file could not be accepted.', 'ultimate-commerce-for-woocommerce'));
        }

        // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents -- Reading a locally validated uploaded file, not a remote URL.
        $contents = file_get_contents($tmpName, false, null, 0, SettingsTransfer::MAX_BYTES + 1);
        if (!is_string($contents) || $contents === '' || strlen($contents) > SettingsTransfer::MAX_BYTES) {
            return new \WP_Error('uc_settings_upload_read', __('The uploaded settings file is empty, unreadable or too large.', 'ultimate-commerce-for-woocommerce'));
        }

        return $contents;
    }

    private static function requireCapability(): void
    {
        if (!current_user_can(Capabilities::MANAGE_SETTINGS)) {
            wp_die(
                esc_html__('You do not have permission to manage Ultimate Commerce settings.', 'ultimate-commerce-for-woocommerce'),
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
                'ulticofo_settings_status' => sanitize_key($status),
            ),
            admin_url('admin.php')
        );
        wp_safe_redirect($url);
        exit;
    }

    private static function noticeCode(): string
    {
        // phpcs:disable WordPress.Security.NonceVerification.Recommended -- Read-only, sanitized admin notice state; it does not authorize or mutate data.
        if (!isset($_GET['ulticofo_settings_status']) || !is_scalar($_GET['ulticofo_settings_status'])) {
            return '';
        }
        $code = sanitize_key((string) wp_unslash($_GET['ulticofo_settings_status']));
        // phpcs:enable WordPress.Security.NonceVerification.Recommended
        return $code;
    }

    private static function renderNotice(string $code): void
    {
        if ($code === 'imported') {
            echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__('Ultimate Commerce settings imported. The imported module preferences are now active.', 'ultimate-commerce-for-woocommerce') . '</p></div>';
            return;
        }

        if ($code === 'retention-saved') {
            echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__('Ultimate Commerce data-retention preference saved.', 'ultimate-commerce-for-woocommerce') . '</p></div>';
            return;
        }

        if ($code === 'invalid') {
            echo '<div class="notice notice-error"><p>' . esc_html__('The settings file was not imported. Use a valid Ultimate Commerce settings export and try again.', 'ultimate-commerce-for-woocommerce') . '</p></div>';
        }
    }
}
