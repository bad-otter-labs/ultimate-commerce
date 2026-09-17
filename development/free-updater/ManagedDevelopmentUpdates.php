<?php

namespace BadOtter\UltimateCommerce\DevelopmentUpdates;

defined('ABSPATH') || exit;

final class ManagedDevelopmentUpdates
{
    private const CACHE = 'uc_free_dev_managed_update_meta';
    private const SYNTHETIC = 'badotter-managed://ultimate-commerce-for-woocommerce-dev/core';

    public static function hooks(): void
    {
        add_filter('pre_set_site_transient_update_plugins', array(__CLASS__, 'check'));
        add_filter('site_transient_update_plugins', array(__CLASS__, 'cached'));
        add_filter('update_plugins_badotter.io', array(__CLASS__, 'updateUri'), 10, 4);
        add_filter('upgrader_pre_download', array(__CLASS__, 'preDownload'), 10, 4);
        add_action('upgrader_process_complete', array(__CLASS__, 'afterUpgrade'), 10, 2);
    }

    public static function updateUri($update, array $pluginData, string $pluginFile, array $locales = array())
    {
        if ($pluginFile !== self::pluginFile()) {
            return $update;
        }

        $result = (new BadOtterDevelopmentClient())->updateLookup();
        if (empty($result['success'])) {
            return false;
        }

        $data = $result['data'];
        $version = sanitize_text_field((string) ($data['version'] ?? $data['newVersion'] ?? ''));
        if ($version === '' || version_compare($version, ULTIMATE_COMMERCE_VERSION, '<=')) {
            return false;
        }

        return array(
            'id' => 'badotter:ultimate-commerce-for-woocommerce-dev',
            'slug' => 'ultimate-commerce-for-woocommerce',
            'version' => $version,
            'url' => 'https://badotter.io/ultimate-commerce',
            'package' => self::SYNTHETIC,
            'requires' => sanitize_text_field((string) ($data['minimumWordPressVersion'] ?? '6.6')),
            'requires_php' => sanitize_text_field((string) ($data['minimumPhpVersion'] ?? '8.1')),
            'autoupdate' => true,
        );
    }

    public static function check($transient)
    {
        if (!is_object($transient)) {
            return $transient;
        }

        $result = (new BadOtterDevelopmentClient())->updateLookup();
        if (!empty($result['success'])) {
            $data = is_array($result['data'] ?? null) ? $result['data'] : array();
            set_site_transient(self::CACHE, $data, !empty($data['updateAvailable']) ? HOUR_IN_SECONDS : 5 * MINUTE_IN_SECONDS);
            return self::apply($transient, $data);
        }

        return self::cached($transient);
    }

    public static function cached($transient)
    {
        if (!is_object($transient)) {
            return $transient;
        }

        $data = get_site_transient(self::CACHE);
        return is_array($data) ? self::apply($transient, $data) : $transient;
    }

    public static function preDownload($reply, string $package, $upgrader, array $hookExtra)
    {
        if ($package !== self::SYNTHETIC) {
            return $reply;
        }

        if (is_admin() && get_current_user_id() && !current_user_can('update_plugins')) {
            return new \WP_Error(
                'uc_free_dev_update_forbidden',
                __('You do not have permission to update Ultimate Commerce for WooCommerce.', 'ultimate-commerce-for-woocommerce')
            );
        }

        $result = (new BadOtterDevelopmentClient())->updateLookup(true);
        if (empty($result['success'])) {
            return new \WP_Error(
                'uc_free_dev_update_lookup',
                sanitize_text_field((string) ($result['message'] ?? 'Development update lookup failed.'))
            );
        }

        $data = is_array($result['data'] ?? null) ? $result['data'] : array();
        $version = sanitize_text_field((string) ($data['version'] ?? $data['newVersion'] ?? ''));
        if ($version === '' || version_compare($version, ULTIMATE_COMMERCE_VERSION, '<=')) {
            return new \WP_Error(
                'uc_free_dev_update_stale',
                __('No newer Ultimate Commerce development release is available.', 'ultimate-commerce-for-woocommerce')
            );
        }

        $descriptor = is_array($data['package'] ?? null) ? $data['package'] : array();
        $url = esc_url_raw((string) ($descriptor['downloadUrl'] ?? $descriptor['url'] ?? ''));
        $expected = strtolower((string) preg_replace('/^sha256:/i', '', trim((string) ($descriptor['checksum'] ?? $descriptor['sha256'] ?? ''))));
        if ($url === '' || !preg_match('/^[a-f0-9]{64}$/', $expected)) {
            return new \WP_Error(
                'uc_free_dev_update_descriptor',
                __('Bad Otter returned an incomplete development package descriptor.', 'ultimate-commerce-for-woocommerce')
            );
        }

        $parts = wp_parse_url($url);
        if (!is_array($parts) || strtolower((string) ($parts['scheme'] ?? '')) !== 'https') {
            return new \WP_Error(
                'uc_free_dev_update_url',
                __('Bad Otter returned an invalid development package URL.', 'ultimate-commerce-for-woocommerce')
            );
        }

        require_once ABSPATH . 'wp-admin/includes/file.php';
        $tmp = wp_tempnam('ultimate-commerce-for-woocommerce-dev.zip');
        if (!$tmp) {
            return new \WP_Error(
                'uc_free_dev_update_temp',
                __('Unable to create a temporary development update file.', 'ultimate-commerce-for-woocommerce')
            );
        }

        $response = wp_safe_remote_get($url, array(
            'timeout' => 60,
            'redirection' => 2,
            'reject_unsafe_urls' => true,
            'sslverify' => true,
            'stream' => true,
            'filename' => $tmp,
        ));
        if (is_wp_error($response)) {
            @unlink($tmp);
            return $response;
        }

        $http = (int) wp_remote_retrieve_response_code($response);
        if ($http < 200 || $http >= 300) {
            @unlink($tmp);
            return new \WP_Error(
                'uc_free_dev_update_download',
                sprintf(
                    __('Development update download failed with HTTP %d.', 'ultimate-commerce-for-woocommerce'),
                    $http
                )
            );
        }

        $actual = strtolower((string) hash_file('sha256', $tmp));
        if (!hash_equals($expected, $actual)) {
            @unlink($tmp);
            return new \WP_Error(
                'uc_free_dev_update_checksum',
                __('Development update checksum verification failed.', 'ultimate-commerce-for-woocommerce')
            );
        }

        $identity = self::verifyArchive($tmp, $version);
        if (is_wp_error($identity)) {
            @unlink($tmp);
            return $identity;
        }

        return $tmp;
    }

    public static function afterUpgrade($upgrader, array $options): void
    {
        if (($options['action'] ?? '') !== 'update' || ($options['type'] ?? '') !== 'plugin') {
            return;
        }

        $plugins = (array) ($options['plugins'] ?? array());
        if (!empty($options['plugin'])) {
            $plugins[] = $options['plugin'];
        }
        if (!in_array(self::pluginFile(), $plugins, true)) {
            return;
        }

        self::flush();
        wp_clean_plugins_cache(true);
    }

    public static function flush(): void
    {
        delete_site_transient(self::CACHE);
        delete_site_transient('update_plugins');
    }

    private static function apply($transient, array $data)
    {
        if (!isset($transient->response) || !is_array($transient->response)) {
            $transient->response = array();
        }
        if (!isset($transient->no_update) || !is_array($transient->no_update)) {
            $transient->no_update = array();
        }

        $version = sanitize_text_field((string) ($data['version'] ?? $data['newVersion'] ?? ''));
        if ($version === '') {
            return $transient;
        }

        $plugin = self::pluginFile();
        $available = !empty($data['updateAvailable']) && version_compare($version, ULTIMATE_COMMERCE_VERSION, '>');
        $update = (object) array(
            'id' => 'badotter:ultimate-commerce-for-woocommerce-dev',
            'slug' => 'ultimate-commerce-for-woocommerce',
            'plugin' => $plugin,
            'new_version' => $version,
            'url' => 'https://badotter.io/ultimate-commerce',
            'package' => $available ? self::SYNTHETIC : '',
            'requires' => (string) ($data['minimumWordPressVersion'] ?? '6.6'),
            'requires_php' => (string) ($data['minimumPhpVersion'] ?? '8.1'),
            'tested' => (string) ($data['testedWordPressVersion'] ?? get_bloginfo('version')),
        );

        if ($available) {
            $transient->response[$plugin] = $update;
            unset($transient->no_update[$plugin]);
        } else {
            $transient->no_update[$plugin] = $update;
            unset($transient->response[$plugin]);
        }

        return $transient;
    }

    private static function verifyArchive(string $file, string $expectedVersion)
    {
        if (!class_exists(\ZipArchive::class)) {
            return true;
        }

        $zip = new \ZipArchive();
        if ($zip->open($file) !== true) {
            return new \WP_Error(
                'uc_free_dev_archive_invalid',
                __('The development update archive could not be opened.', 'ultimate-commerce-for-woocommerce')
            );
        }

        $entry = 'ultimate-commerce-for-woocommerce/ultimate-commerce-for-woocommerce.php';
        $contents = $zip->getFromName($entry);
        $zip->close();
        if (!is_string($contents)) {
            return new \WP_Error(
                'uc_free_dev_archive_identity',
                __('The development update archive does not contain the canonical Ultimate Commerce plugin root.', 'ultimate-commerce-for-woocommerce')
            );
        }

        if (!preg_match('/^\s*\*\s*Plugin Name:\s*Ultimate Commerce for WooCommerce\s*$/mi', $contents)) {
            return new \WP_Error(
                'uc_free_dev_archive_identity',
                __('The development update archive plugin identity is invalid.', 'ultimate-commerce-for-woocommerce')
            );
        }

        if ($expectedVersion !== '' && preg_match('/^\s*\*\s*Version:\s*([^\s]+)\s*$/mi', $contents, $match) && $match[1] !== $expectedVersion) {
            return new \WP_Error(
                'uc_free_dev_archive_version',
                __('The development update archive version does not match the published release.', 'ultimate-commerce-for-woocommerce')
            );
        }

        return true;
    }

    private static function pluginFile(): string
    {
        return plugin_basename(ULTIMATE_COMMERCE_FILE);
    }
}
