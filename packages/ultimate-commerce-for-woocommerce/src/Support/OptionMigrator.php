<?php

namespace BadOtter\UltimateCommerce\Support;

defined('ABSPATH') || exit;

final class OptionMigrator
{
    /**
     * Canonical WordPress.org-safe option names and the pre-release names that
     * may already exist on development or early adopter installations.
     *
     * @var array<string, list<string>>
     */
    private const LEGACY_OPTIONS = array(
        'ulticofo_version' => array('uc_version', 'ultimate_commerce_version'),
        'ulticofo_schema_version' => array('uc_schema_version', 'ultimate_commerce_schema_version'),
        'ulticofo_modules' => array('uc_modules', 'ultimate_commerce_modules'),
        'ulticofo_delete_data_on_uninstall' => array('uc_delete_data_on_uninstall'),
        'ulticofo_capability_version' => array('uc_capability_version'),
        'ulticofo_secret_store_v1' => array('uc_secret_store_v1'),
    );

    public static function migrate(): void
    {
        foreach (self::LEGACY_OPTIONS as $canonicalName => $legacyNames) {
            $missing = new \stdClass();
            if (get_option($canonicalName, $missing) !== $missing) {
                continue;
            }

            foreach ($legacyNames as $legacyName) {
                $legacyValue = get_option($legacyName, $missing);
                if ($legacyValue === $missing) {
                    continue;
                }

                add_option($canonicalName, $legacyValue, '', false);
                break;
            }
        }
    }
}
