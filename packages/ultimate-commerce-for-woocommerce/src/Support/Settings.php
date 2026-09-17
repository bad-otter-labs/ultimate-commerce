<?php

namespace BadOtter\UltimateCommerce\Support;

defined('ABSPATH') || exit;

final class Settings
{
    private const MODULE_OPTION = 'uc_modules';
    public const UNINSTALL_DATA_OPTION = 'uc_delete_data_on_uninstall';

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

    /**
     * Update explicit module preferences without discarding states for modules
     * that are temporarily unregistered (for example, a deactivated extension).
     *
     * @param array<string, bool> $states Module key => enabled preference.
     * @return array<string, bool>
     */
    public static function updateModuleStates(array $states): array
    {
        $stored = self::moduleStates();

        foreach ($states as $key => $enabled) {
            if (!is_string($key) || $key === '' || sanitize_key($key) !== $key) {
                continue;
            }
            $stored[$key] = (bool) $enabled;
        }

        ksort($stored);
        update_option(self::MODULE_OPTION, $stored, false);

        return $stored;
    }

    public static function deleteDataOnUninstall(): bool
    {
        return (bool) get_option(self::UNINSTALL_DATA_OPTION, false);
    }

    public static function updateDeleteDataOnUninstall(bool $enabled): void
    {
        update_option(self::UNINSTALL_DATA_OPTION, $enabled, false);
    }
}
