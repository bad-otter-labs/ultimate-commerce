<?php

namespace BadOtter\UltimateCommerce\Wishlist;

use BadOtter\UltimateCommerce\Rest\RouteRegistrar;
use BadOtter\UltimateCommerce\Security\Authorization;

defined('ABSPATH') || exit;

final class WishlistRestController
{
    public static function hooks(): void
    {
        add_action('rest_api_init', array(__CLASS__, 'registerRoutes'));
    }

    public static function registerRoutes(): void
    {
        RouteRegistrar::register('/wishlist', array(
            'methods' => 'GET',
            'callback' => array(__CLASS__, 'read'),
            'permission_callback' => array(__CLASS__, 'permission'),
        ));

        RouteRegistrar::register('/wishlist/items', array(
            'methods' => 'POST',
            'callback' => array(__CLASS__, 'add'),
            'permission_callback' => array(__CLASS__, 'permission'),
            'args' => array(
                'product_id' => self::productIdArg(),
            ),
        ));

        RouteRegistrar::register('/wishlist/items/(?P<product_id>[\d]+)', array(
            'methods' => 'DELETE',
            'callback' => array(__CLASS__, 'remove'),
            'permission_callback' => array(__CLASS__, 'permission'),
            'args' => array(
                'product_id' => self::productIdArg(),
            ),
        ));

        RouteRegistrar::register('/wishlist/merge', array(
            'methods' => 'POST',
            'callback' => array(__CLASS__, 'merge'),
            'permission_callback' => array(__CLASS__, 'permission'),
            'args' => array(
                'product_ids' => array(
                    'type' => 'array',
                    'required' => true,
                    'items' => array('type' => 'integer'),
                    'validate_callback' => static fn($value): bool => is_array($value) && count($value) <= WishlistStore::MAX_ITEMS,
                ),
            ),
        ));
    }

    /** @return true|\WP_Error */
    public static function permission()
    {
        return Authorization::requireAuthenticated();
    }

    /** @return \WP_REST_Response|array<string, mixed> */
    public static function read()
    {
        return self::response(WishlistStore::ids((int) get_current_user_id()));
    }

    /** @return \WP_REST_Response|\WP_Error|array<string, mixed> */
    public static function add(\WP_REST_Request $request)
    {
        $ids = WishlistStore::add((int) get_current_user_id(), (int) $request->get_param('product_id'));
        return $ids instanceof \WP_Error ? $ids : self::response($ids);
    }

    /** @return \WP_REST_Response|array<string, mixed> */
    public static function remove(\WP_REST_Request $request)
    {
        return self::response(WishlistStore::remove(
            (int) get_current_user_id(),
            (int) $request->get_param('product_id')
        ));
    }

    /** @return \WP_REST_Response|array<string, mixed> */
    public static function merge(\WP_REST_Request $request)
    {
        $productIds = $request->get_param('product_ids');
        return self::response(WishlistStore::merge(
            (int) get_current_user_id(),
            is_array($productIds) ? $productIds : array()
        ));
    }

    /** @return array<string, mixed> */
    private static function productIdArg(): array
    {
        return array(
            'type' => 'integer',
            'required' => true,
            'minimum' => 1,
            'sanitize_callback' => static fn($value): int => absint($value),
            'validate_callback' => static fn($value): bool => is_numeric($value) && (int) $value > 0,
        );
    }

    /** @param list<int> $ids
     *  @return \WP_REST_Response|array<string, mixed>
     */
    private static function response(array $ids)
    {
        $payload = array(
            'product_ids' => array_values($ids),
            'max_items' => WishlistStore::MAX_ITEMS,
        );

        return function_exists('rest_ensure_response') ? rest_ensure_response($payload) : $payload;
    }
}
