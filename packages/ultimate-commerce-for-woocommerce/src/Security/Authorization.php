<?php

namespace BadOtter\UltimateCommerce\Security;

defined('ABSPATH') || exit;

final class Authorization
{
    public static function currentUserCan(string $capability): bool
    {
        return $capability !== '' && current_user_can($capability);
    }

    /** @return true|\WP_Error */
    public static function requireCapability(string $capability)
    {
        if (self::currentUserCan($capability)) {
            return true;
        }

        return new \WP_Error(
            'uc_forbidden',
            __('You do not have permission to perform this action.', 'ultimate-commerce-for-woocommerce'),
            array('status' => 403)
        );
    }

    public static function owns(int $ownerUserId, ?int $actorUserId = null): bool
    {
        $actorUserId = $actorUserId ?? (int) get_current_user_id();
        return $ownerUserId > 0 && $actorUserId > 0 && $ownerUserId === $actorUserId;
    }

    /** @return true|\WP_Error */
    public static function requireOwnership(int $ownerUserId, ?int $actorUserId = null)
    {
        if (self::owns($ownerUserId, $actorUserId)) {
            return true;
        }

        return new \WP_Error(
            'uc_object_forbidden',
            __('You do not have permission to access this resource.', 'ultimate-commerce-for-woocommerce'),
            array('status' => 403)
        );
    }

    /** @return true|\WP_Error */
    public static function requireAuthenticated()
    {
        if (is_user_logged_in() && (int) get_current_user_id() > 0) {
            return true;
        }

        return new \WP_Error(
            'uc_authentication_required',
            __('Authentication is required.', 'ultimate-commerce-for-woocommerce'),
            array('status' => 401)
        );
    }
}
