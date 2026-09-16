<?php

namespace BadOtter\UltimateCommerce\Security;

defined('ABSPATH') || exit;

final class Csrf
{
    private const ACTION_PREFIX = 'uc_';

    public static function verify(string $nonce, string $action): bool
    {
        $action = sanitize_key($action);
        if ($nonce === '' || $action === '') {
            return false;
        }

        return false !== wp_verify_nonce($nonce, self::ACTION_PREFIX . $action);
    }

    /** @return true|\WP_Error */
    public static function require(string $nonce, string $action)
    {
        if (self::verify($nonce, $action)) {
            return true;
        }

        return new \WP_Error(
            'uc_invalid_nonce',
            __('The request could not be verified.', 'ultimate-commerce-for-woocommerce'),
            array('status' => 403)
        );
    }
}
