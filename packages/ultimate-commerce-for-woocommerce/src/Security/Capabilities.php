<?php

namespace BadOtter\UltimateCommerce\Security;

defined('ABSPATH') || exit;

final class Capabilities
{
    private const VERSION = '2';
    private const VERSION_OPTION = 'ulticofo_capability_version';

    public const VIEW_DIAGNOSTICS = 'ulticofo_view_diagnostics';
    public const MANAGE_SETTINGS = 'ulticofo_manage_settings';

    /** @return list<string> */
    public static function all(): array
    {
        return array(
            self::VIEW_DIAGNOSTICS,
            self::MANAGE_SETTINGS,
        );
    }

    /** @return list<string> */
    private static function legacy(): array
    {
        return array(
            'uc_view_diagnostics',
            'uc_manage_settings',
        );
    }

    public static function maybeInstall(): void
    {
        if ((string) get_option(self::VERSION_OPTION, '') === self::VERSION) {
            return;
        }

        self::install();
    }

    public static function install(): void
    {
        $administrator = get_role('administrator');
        if ($administrator && method_exists($administrator, 'add_cap')) {
            foreach (self::all() as $capability) {
                $administrator->add_cap($capability);
            }
        }

        $shopManager = get_role('shop_manager');
        if ($shopManager && method_exists($shopManager, 'add_cap')) {
            $shopManager->add_cap(self::VIEW_DIAGNOSTICS);
        }

        update_option(self::VERSION_OPTION, self::VERSION, false);
    }

    public static function remove(): void
    {
        foreach (array('administrator', 'shop_manager') as $roleName) {
            $role = get_role($roleName);
            if (!$role || !method_exists($role, 'remove_cap')) {
                continue;
            }
            foreach (array_merge(self::all(), self::legacy()) as $capability) {
                $role->remove_cap($capability);
            }
        }

        delete_option(self::VERSION_OPTION);
        delete_option('uc_capability_version');
    }
}
