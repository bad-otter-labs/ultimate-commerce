<?php

namespace BadOtter\UltimateCommerce\Support;

defined('ABSPATH') || exit;

final class Settings
{
    private const MODULE_OPTION = 'ultimate_commerce_modules';

    public static function moduleEnabled(string $key): bool
    {
        $stored = get_option(self::MODULE_OPTION, array());
        $enabled = !is_array($stored) || !array_key_exists($key, $stored) ? true : (bool) $stored[$key];

        return (bool) apply_filters('uc_module_enabled', $enabled, $key);
    }

    public static function moduleStates(): array
    {
        $stored = get_option(self::MODULE_OPTION, array());
        return is_array($stored) ? $stored : array();
    }
}
