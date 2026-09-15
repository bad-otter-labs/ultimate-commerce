<?php

namespace BadOtter\UltimateCommerce\Updates;

defined('ABSPATH') || exit;

final class ManagedUpdates
{
    private const CACHE_KEY = 'uc_bad_otter_core_update';
    private const PACKAGE_SCHEME = 'uc-bad-otter://core';

    public static function hooks(): void
    {
        add_filter('pre_set_site_transient_update_plugins', array(__CLASS__, 'inject'));
        add_filter('site_transient_update_plugins', array(__CLASS__, 'inject'));
        add_filter('update_plugins_badotter.io', array(__CLASS__, 'updateUri'), 10, 4);
        add_filter('upgrader_pre_download', array(__CLASS__, 'preDownload'), 10, 4);
    }

    public static function updateUri($update, array $pluginData, string $pluginFile, array $locales = array())
    {
        if ($pluginFile !== plugin_basename(ULTIMATE_COMMERCE_FILE)) {
            return $update;
        }

        $data = self::lookup();
        $version = sanitize_text_field((string) ($data['version'] ?? ''));
        if ($version === '' || version_compare($version, ULTIMATE_COMMERCE_VERSION, '<=')) {
            return false;
        }

        return array(
            'id' => 'badotter:ultimate-commerce',
            'slug' => dirname(plugin_basename(ULTIMATE_COMMERCE_FILE)),
            'version' => $version,
            'url' => 'https://badotter.io/ultimate-commerce',
            'package' => self::PACKAGE_SCHEME,
            'requires' => sanitize_text_field((string) ($data['minimumWordPressVersion'] ?? '')),
            'requires_php' => sanitize_text_field((string) ($data['minimumPhpVersion'] ?? '')),
            'autoupdate' => true,
        );
    }

    public static function inject($transient)
    {
        if (!is_object($transient)) {
            return $transient;
        }
        if (!isset($transient->response) || !is_array($transient->response)) {
            $transient->response = array();
        }

        $plugin = plugin_basename(ULTIMATE_COMMERCE_FILE);
        $data = self::lookup();
        $version = sanitize_text_field((string) ($data['version'] ?? ''));
        if ($version === '' || version_compare($version, ULTIMATE_COMMERCE_VERSION, '<=')) {
            return $transient;
        }

        $update = new \stdClass();
        $update->id = 'badotter:ultimate-commerce';
        $update->slug = dirname($plugin);
        $update->plugin = $plugin;
        $update->new_version = $version;
        $update->url = 'https://badotter.io/ultimate-commerce';
        $update->package = self::PACKAGE_SCHEME;
        if (!empty($data['minimumWordPressVersion'])) {
            $update->requires = sanitize_text_field((string) $data['minimumWordPressVersion']);
        }
        if (!empty($data['minimumPhpVersion'])) {
            $update->requires_php = sanitize_text_field((string) $data['minimumPhpVersion']);
        }
        $transient->response[$plugin] = $update;

        return $transient;
    }

    public static function preDownload($reply, $package, $upgrader, $hookExtra)
    {
        if ($package !== self::PACKAGE_SCHEME) {
            return $reply;
        }

        if (is_admin() && get_current_user_id() && !current_user_can('update_plugins')) {
            return new \WP_Error('uc_update_forbidden', __('You do not have permission to update Ultimate Commerce.', 'ultimate-commerce'));
        }

        $result = (new BadOtterClient())->updateLookup(ULTIMATE_COMMERCE_VERSION);
        if (empty($result['success'])) {
            return new \WP_Error('uc_update_lookup_failed', (string) ($result['message'] ?? 'Update lookup failed.'));
        }

        $data = is_array($result['data'] ?? null) ? $result['data'] : array();
        $packageData = is_array($data['package'] ?? null) ? $data['package'] : array();
        $download = (string) ($packageData['downloadUrl'] ?? $packageData['download'] ?? $data['downloadUrl'] ?? $data['download'] ?? '');
        $checksum = (string) ($packageData['checksum'] ?? $data['checksum'] ?? '');
        $version = sanitize_text_field((string) ($data['version'] ?? ''));

        if ($download === '' || $checksum === '') {
            return new \WP_Error('uc_package_descriptor_invalid', __('Bad Otter did not return a complete verified package descriptor.', 'ultimate-commerce'));
        }

        $url = (new BadOtterClient())->absoluteDownloadUrl($download);
        if ($url === '') {
            return new \WP_Error('uc_package_url_invalid', __('Bad Otter returned an invalid package URL.', 'ultimate-commerce'));
        }

        require_once ABSPATH . 'wp-admin/includes/file.php';
        $tmp = download_url($url, 30);
        if (is_wp_error($tmp)) {
            return $tmp;
        }

        $expected = strtolower(preg_replace('/^sha256:/i', '', trim($checksum)));
        $actual = strtolower(hash_file('sha256', $tmp));
        if (!preg_match('/^[a-f0-9]{64}$/', $expected) || !hash_equals($expected, $actual)) {
            @unlink($tmp);
            return new \WP_Error('uc_checksum_mismatch', __('Bad Otter package checksum verification failed. The update was stopped.', 'ultimate-commerce'));
        }

        $identity = self::verifyArchive($tmp, $version);
        if (is_wp_error($identity)) {
            @unlink($tmp);
            return $identity;
        }

        return $tmp;
    }

    public static function flush(): void
    {
        delete_transient(self::CACHE_KEY);
        delete_site_transient('update_plugins');
    }

    private static function lookup(): array
    {
        $cached = get_transient(self::CACHE_KEY);
        if (is_array($cached)) {
            return $cached;
        }

        $result = (new BadOtterClient())->updateLookup(ULTIMATE_COMMERCE_VERSION);
        if (empty($result['success'])) {
            return array();
        }

        $data = is_array($result['data'] ?? null) ? $result['data'] : array();
        set_transient(self::CACHE_KEY, $data, !empty($data['updateAvailable']) ? HOUR_IN_SECONDS : 5 * MINUTE_IN_SECONDS);
        return $data;
    }

    private static function verifyArchive(string $file, string $expectedVersion)
    {
        if (!class_exists(\ZipArchive::class)) {
            return true;
        }

        $zip = new \ZipArchive();
        if ($zip->open($file) !== true) {
            return new \WP_Error('uc_archive_invalid', __('The update archive could not be opened.', 'ultimate-commerce'));
        }

        $entry = 'ultimate-commerce/ultimate-commerce.php';
        $contents = $zip->getFromName($entry);
        $zip->close();
        if (!is_string($contents)) {
            return new \WP_Error('uc_archive_identity_failed', __('The update archive does not contain the Ultimate Commerce plugin root.', 'ultimate-commerce'));
        }

        if (!preg_match('/^\s*\*\s*Plugin Name:\s*Ultimate Commerce\s*$/mi', $contents)) {
            return new \WP_Error('uc_archive_identity_failed', __('The update archive plugin identity did not match Ultimate Commerce.', 'ultimate-commerce'));
        }

        if ($expectedVersion !== '' && preg_match('/^\s*\*\s*Version:\s*([^\s]+)\s*$/mi', $contents, $match) && $match[1] !== $expectedVersion) {
            return new \WP_Error('uc_archive_version_failed', __('The update archive version did not match the published release.', 'ultimate-commerce'));
        }

        return true;
    }
}
