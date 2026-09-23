<?php

namespace BadOtter\UltimateCommerce\Privacy;

use BadOtter\UltimateCommerce\Security\Capabilities;
use BadOtter\UltimateCommerce\Support\Settings;
use BadOtter\UltimateCommerce\Wishlist\WishlistStore;

defined('ABSPATH') || exit;

final class Uninstall
{
    private const ALWAYS_OPTIONS = array(
        'ulticofo_version',
        'ulticofo_schema_version',
        'uc_version',
        'uc_schema_version',
        'ultimate_commerce_version',
        'ultimate_commerce_schema_version',
    );

    private const PURGE_OPTIONS = array(
        'ulticofo_modules',
        'ulticofo_secret_store_v1',
        Settings::UNINSTALL_DATA_OPTION,
        'uc_modules',
        'uc_secret_store_v1',
        'uc_delete_data_on_uninstall',
        'ultimate_commerce_modules',
    );

    private const RUNTIME_OPTION_PREFIXES = array(
        'ulticofo_lock_',
        'ulticofo_idem_',
        'ulticofo_replay_',
        'uc_lock_',
        'uc_idem_',
        'uc_replay_',
    );

    private const RUNTIME_TRANSIENT_PREFIXES = array(
        '_transient_ulticofo_rl_',
        '_transient_timeout_ulticofo_rl_',
        '_transient_uc_rl_',
        '_transient_timeout_uc_rl_',
    );

    public static function run(): void
    {
        if (function_exists('is_multisite') && is_multisite() && function_exists('get_sites')) {
            $siteIds = get_sites(array('fields' => 'ids', 'number' => 0));
            foreach ((array) $siteIds as $siteId) {
                if (!function_exists('switch_to_blog') || !function_exists('restore_current_blog')) {
                    break;
                }
                switch_to_blog((int) $siteId);
                self::cleanupCurrentSite();
                restore_current_blog();
            }
            return;
        }

        self::cleanupCurrentSite();
    }

    private static function cleanupCurrentSite(): void
    {
        $purgeMerchantData = Settings::deleteDataOnUninstall();

        Capabilities::remove();
        self::deleteOptions(self::ALWAYS_OPTIONS);
        self::deleteOptionsWithPrefixes(self::RUNTIME_OPTION_PREFIXES);
        self::deleteOptionsWithPrefixes(self::RUNTIME_TRANSIENT_PREFIXES);

        if ($purgeMerchantData) {
            self::deleteOptions(self::PURGE_OPTIONS);
            self::deleteUserMeta(array(WishlistStore::metaKey(), WishlistStore::legacyMetaKey()));
        }
    }

    /** @param list<string> $names */
    private static function deleteOptions(array $names): void
    {
        foreach ($names as $name) {
            delete_option($name);
        }
    }

    /** @param list<string> $keys */
    private static function deleteUserMeta(array $keys): void
    {
        if (!function_exists('delete_metadata')) {
            return;
        }

        foreach ($keys as $key) {
            if (is_string($key)
                && (str_starts_with($key, WishlistStore::BASE_META_KEY)
                    || str_starts_with($key, WishlistStore::LEGACY_META_KEY))) {
                delete_metadata('user', 0, $key, '', true);
            }
        }
    }

    /** @param list<string> $prefixes */
    private static function deleteOptionsWithPrefixes(array $prefixes): void
    {
        global $wpdb;

        if (!is_object($wpdb)
            || !isset($wpdb->options)
            || !method_exists($wpdb, 'esc_like')
            || !method_exists($wpdb, 'prepare')
            || !method_exists($wpdb, 'get_col')) {
            return;
        }

        foreach ($prefixes as $prefix) {
            $like = $wpdb->esc_like($prefix) . '%';
            // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- Uninstall must enumerate non-autoloaded runtime options by a strict UC-owned prefix.
            $names = $wpdb->get_col($wpdb->prepare("SELECT option_name FROM {$wpdb->options} WHERE option_name LIKE %s", $like));
            foreach ((array) $names as $name) {
                if (is_string($name) && str_starts_with($name, $prefix)) {
                    delete_option($name);
                }
            }
        }
    }
}
