<?php

namespace BadOtter\UltimateCommerce\Account;

use BadOtter\UltimateCommerce\Security\Authorization;

defined('ABSPATH') || exit;

final class AccountViewModel
{
    public const MAX_RECENT_ORDERS = 20;

    /** @return array<string, mixed>|\WP_Error */
    public static function forCurrentUser(int $recentOrderLimit = 5)
    {
        $authenticated = Authorization::requireAuthenticated();
        if (is_wp_error($authenticated)) {
            return $authenticated;
        }

        $userId = (int) get_current_user_id();
        $user = function_exists('get_userdata') ? get_userdata($userId) : false;
        if (!is_object($user)) {
            return new \WP_Error(
                'uc_account_not_found',
                __('Customer account not found.', 'ultimate-commerce-for-woocommerce'),
                array('status' => 404)
            );
        }

        $limit = max(1, min(self::MAX_RECENT_ORDERS, $recentOrderLimit));
        $filteredLimit = apply_filters('uc_account_recent_order_limit', $limit, $userId);
        $limit = is_scalar($filteredLimit)
            ? max(1, min(self::MAX_RECENT_ORDERS, absint($filteredLimit)))
            : $limit;

        $orders = function_exists('wc_get_orders')
            ? wc_get_orders(
                array(
                    'customer_id' => $userId,
                    'limit' => $limit,
                    'orderby' => 'date',
                    'order' => 'DESC',
                    'return' => 'objects',
                )
            )
            : array();

        $orderModels = array();
        foreach (is_array($orders) ? $orders : array() as $order) {
            $model = OrderViewModel::fromOrder($order);
            if (is_array($model)) {
                $orderModels[] = $model;
            }
        }

        $data = array(
            'schema' => 'uc.account.v1',
            'customer' => array(
                'id' => $userId,
                'display_name' => isset($user->display_name) ? (string) $user->display_name : '',
            ),
            'recent_orders' => $orderModels,
            'urls' => array(
                'account' => function_exists('wc_get_page_permalink') ? (string) wc_get_page_permalink('myaccount') : '',
                'orders' => function_exists('wc_get_account_endpoint_url') ? (string) wc_get_account_endpoint_url('orders') : '',
            ),
            'slots' => array('overview', 'after_overview', 'orders', 'after_orders'),
            'cache' => array(
                'public_cache_safe' => false,
                'contains_customer_identity' => true,
                'contains_nonce' => false,
            ),
        );

        $data = apply_filters('uc_account_view_model', $data, $userId);
        return is_array($data) ? $data : array();
    }
}
