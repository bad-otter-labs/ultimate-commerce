<?php

namespace BadOtter\UltimateCommerce\Account;

use BadOtter\UltimateCommerce\Security\Authorization;

defined('ABSPATH') || exit;

final class OrderViewModel
{
    /**
     * Build a customer-facing representation of a WooCommerce order.
     *
     * Guest orders intentionally fail closed. Guest-order association to a later
     * account requires a separate verified workflow and is not part of API v1.
     *
     * @param int|\WC_Order $order
     * @return array<string, mixed>|\WP_Error
     */
    public static function fromOrder($order)
    {
        $order = is_numeric($order) && function_exists('wc_get_order')
            ? wc_get_order((int) $order)
            : $order;

        if (!$order instanceof \WC_Order) {
            return new \WP_Error(
                'uc_order_not_found',
                __('Order not found.', 'ultimate-commerce-for-woocommerce'),
                array('status' => 404)
            );
        }

        $actorUserId = (int) get_current_user_id();
        $ownership = Authorization::requireOwnership((int) $order->get_customer_id(), $actorUserId);
        if (is_wp_error($ownership)) {
            return $ownership;
        }

        $created = $order->get_date_created();
        $items = array();
        foreach ((array) $order->get_items('line_item') as $itemId => $item) {
            if (!is_object($item)) {
                continue;
            }
            $items[] = array(
                'id' => (int) $itemId,
                'product_id' => method_exists($item, 'get_product_id') ? (int) $item->get_product_id() : 0,
                'variation_id' => method_exists($item, 'get_variation_id') ? (int) $item->get_variation_id() : 0,
                'name' => method_exists($item, 'get_name') ? (string) $item->get_name() : '',
                'quantity' => method_exists($item, 'get_quantity') ? (int) $item->get_quantity() : 0,
                'subtotal' => method_exists($item, 'get_subtotal') ? (string) $item->get_subtotal() : '',
                'total' => method_exists($item, 'get_total') ? (string) $item->get_total() : '',
            );
        }

        $status = (string) $order->get_status();
        $data = array(
            'schema' => 'uc.order.v1',
            'id' => (int) $order->get_id(),
            'number' => (string) $order->get_order_number(),
            'status' => $status,
            'status_label' => function_exists('wc_get_order_status_name') ? (string) wc_get_order_status_name($status) : $status,
            'created_at' => is_object($created) && method_exists($created, 'date') ? (string) $created->date(DATE_ATOM) : null,
            'currency' => (string) $order->get_currency(),
            'total' => (string) $order->get_total(),
            'item_count' => (int) $order->get_item_count(),
            'items' => $items,
            'view_url' => method_exists($order, 'get_view_order_url') ? (string) $order->get_view_order_url() : '',
            'slots' => array('before_summary', 'after_summary', 'before_items', 'after_items', 'actions'),
            'ownership' => array('verified_for_current_actor' => true),
            'cache' => array(
                'public_cache_safe' => false,
                'contains_customer_identity' => true,
                'contains_nonce' => false,
            ),
        );

        $data = apply_filters('ultimate_commerce_order_view_model', $data, $order, $actorUserId);
        return is_array($data) ? $data : array();
    }
}
