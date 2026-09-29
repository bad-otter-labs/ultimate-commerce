<?php

namespace BadOtter\UltimateCommerce\Support;

defined('ABSPATH') || exit;

final class SettingsTransfer
{
    public const MAX_BYTES = 65536;

    private const FORMAT = 'ultimate-commerce-settings';
    private const SCHEMA_VERSION = 1;
    private const PRODUCT = 'bad-otter-ultimate-commerce-woocommerce';
    private const MAX_MODULES = 128;
    private const MAX_MODULE_KEY_LENGTH = 64;

    /** @return string|\WP_Error */
    public static function exportJson()
    {
        $modules = self::exportableModules();
        if ($modules instanceof \WP_Error) {
            return $modules;
        }

        $payload = array(
            'format' => self::FORMAT,
            'schema_version' => self::SCHEMA_VERSION,
            'product' => self::PRODUCT,
            'settings' => array(
                'modules' => (object) $modules,
            ),
        );

        $json = wp_json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
        if (!is_string($json) || $json === '') {
            return self::error('uc_settings_export_failed', __('Ultimate Commerce could not encode the settings export.', 'bad-otter-ultimate-commerce-woocommerce'));
        }

        if (strlen($json) > self::MAX_BYTES) {
            return self::error('uc_settings_export_too_large', __('Ultimate Commerce settings exceed the supported export size.', 'bad-otter-ultimate-commerce-woocommerce'));
        }

        return $json . "\n";
    }

    /** @return array<string, bool>|\WP_Error */
    public static function importJson(string $json)
    {
        if ($json === '' || strlen($json) > self::MAX_BYTES) {
            return self::error('uc_settings_import_size', __('The Ultimate Commerce settings file is empty or too large.', 'bad-otter-ultimate-commerce-woocommerce'));
        }

        $payload = json_decode($json, false, 32);
        if (!$payload instanceof \stdClass || json_last_error() !== JSON_ERROR_NONE) {
            return self::error('uc_settings_import_json', __('The Ultimate Commerce settings file is not valid JSON.', 'bad-otter-ultimate-commerce-woocommerce'));
        }

        if (!self::hasExactProperties($payload, array('format', 'product', 'schema_version', 'settings'))) {
            return self::error('uc_settings_import_shape', __('The Ultimate Commerce settings file has an unsupported structure.', 'bad-otter-ultimate-commerce-woocommerce'));
        }

        if (!is_string($payload->format) || $payload->format !== self::FORMAT) {
            return self::error('uc_settings_import_format', __('The settings file is not an Ultimate Commerce settings export.', 'bad-otter-ultimate-commerce-woocommerce'));
        }

        if (!is_string($payload->product) || $payload->product !== self::PRODUCT) {
            return self::error('uc_settings_import_product', __('The settings file targets a different product.', 'bad-otter-ultimate-commerce-woocommerce'));
        }

        if (!is_int($payload->schema_version) || $payload->schema_version !== self::SCHEMA_VERSION) {
            return self::error('uc_settings_import_schema', __('The Ultimate Commerce settings schema is not supported by this version.', 'bad-otter-ultimate-commerce-woocommerce'));
        }

        if (!$payload->settings instanceof \stdClass || !self::hasExactProperties($payload->settings, array('modules'))) {
            return self::error('uc_settings_import_settings', __('The Ultimate Commerce settings section is not supported.', 'bad-otter-ultimate-commerce-woocommerce'));
        }

        if (!$payload->settings->modules instanceof \stdClass) {
            return self::error('uc_settings_import_modules', __('Module settings must be a JSON object.', 'bad-otter-ultimate-commerce-woocommerce'));
        }

        $rawModules = get_object_vars($payload->settings->modules);
        if (count($rawModules) > self::MAX_MODULES) {
            return self::error('uc_settings_import_module_limit', __('The settings file contains too many module preferences.', 'bad-otter-ultimate-commerce-woocommerce'));
        }

        $states = array();
        foreach ($rawModules as $key => $enabled) {
            if (!self::validModuleKey($key) || !is_bool($enabled)) {
                return self::error('uc_settings_import_module_value', __('The settings file contains an invalid module preference.', 'bad-otter-ultimate-commerce-woocommerce'));
            }
            $states[$key] = $enabled;
        }

        ksort($states);
        return Settings::updateModuleStates($states);
    }

    /** @return array<string, bool>|\WP_Error */
    private static function exportableModules()
    {
        $modules = array();
        foreach (Settings::moduleStates() as $key => $enabled) {
            if (!is_string($key) || !self::validModuleKey($key)) {
                continue;
            }
            $modules[$key] = (bool) $enabled;
        }

        if (count($modules) > self::MAX_MODULES) {
            return self::error('uc_settings_export_module_limit', __('Too many module preferences are stored to create a portable settings file.', 'bad-otter-ultimate-commerce-woocommerce'));
        }

        ksort($modules);
        return $modules;
    }

    /** @param list<string> $expected */
    private static function hasExactProperties(\stdClass $object, array $expected): bool
    {
        $actual = array_keys(get_object_vars($object));
        sort($actual);
        sort($expected);
        return $actual === $expected;
    }

    private static function validModuleKey(string $key): bool
    {
        return $key !== ''
            && strlen($key) <= self::MAX_MODULE_KEY_LENGTH
            && sanitize_key($key) === $key;
    }

    private static function error(string $code, string $message): \WP_Error
    {
        return new \WP_Error($code, $message, array('status' => 400));
    }
}
