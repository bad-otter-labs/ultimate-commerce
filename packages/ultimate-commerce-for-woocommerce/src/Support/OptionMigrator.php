<?php

namespace BadOtter\UltimateCommerce\Support;

defined('ABSPATH') || exit;

final class OptionMigrator
{
    private const LEGACY_OPTIONS = array(
        'ultimate_commerce_version' => 'uc_version',
        'ultimate_commerce_schema_version' => 'uc_schema_version',
        'ultimate_commerce_modules' => 'uc_modules',
    );

    public static function migrate(): void
    {
        foreach (self::LEGACY_OPTIONS as $legacyName => $canonicalName) {
            $missing = new \stdClass();
            if (get_option($canonicalName, $missing) !== $missing) {
                continue;
            }

            $legacyValue = get_option($legacyName, $missing);
            if ($legacyValue === $missing) {
                continue;
            }

            add_option($canonicalName, $legacyValue, '', false);
        }
    }
}
