<?php

namespace BadOtter\UltimateCommerce\Security;

defined('ABSPATH') || exit;

final class Capabilities
{
    private const VERSION = '1';
    private const VERSION_OPTION = 'uc_capability_version';

    public const VIEW_DIAGNOSTICS = 'uc_view_diagnostics';
    public const MANAGE_SETTINGS = 'uc_manage_settings';

    /** @return list<string> */
    public static function all(): array
    {
        return array(
            self::VIEW_DIAGNOSTICS,
            self::MANAGE_SETTINGS,
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
}
