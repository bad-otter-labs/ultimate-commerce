<?php

namespace BadOtter\UltimateCommerce\Wishlist;

defined('ABSPATH') || exit;

final class WishlistBootstrap
{
    public const ACTION = 'uc_wishlist_bootstrap';

    private static bool $hooksRegistered = false;

    public static function hooks(): void
    {
        if (self::$hooksRegistered) {
            return;
        }

        self::$hooksRegistered = true;
        add_action('wp_ajax_' . self::ACTION, array(__CLASS__, 'respond'));
        add_action('wp_ajax_nopriv_' . self::ACTION, array(__CLASS__, 'respond'));
    }

    public static function url(): string
    {
        return add_query_arg('action', self::ACTION, admin_url('admin-ajax.php'));
    }

    public static function respond(): void
    {
        if (function_exists('nocache_headers')) {
            nocache_headers();
        }

        $loggedIn = is_user_logged_in() && (int) get_current_user_id() > 0;
        $payload = array(
            'logged_in' => $loggedIn,
            'product_ids' => array(),
            'rest_nonce' => '',
        );

        if ($loggedIn) {
            $payload['product_ids'] = WishlistStore::ids((int) get_current_user_id());
            $payload['rest_nonce'] = wp_create_nonce('wp_rest');
        }

        wp_send_json_success($payload);
    }
}
