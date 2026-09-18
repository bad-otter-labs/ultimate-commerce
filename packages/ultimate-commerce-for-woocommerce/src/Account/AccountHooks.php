<?php

namespace BadOtter\UltimateCommerce\Account;

defined('ABSPATH') || exit;

final class AccountHooks
{
    public static function hooks(): void
    {
        add_action('woocommerce_account_dashboard', array(__CLASS__, 'emitAccountModel'), 5);
        add_action('woocommerce_order_details_before_order_table', array(__CLASS__, 'emitOrderModel'), 5);
    }

    public static function emitAccountModel(): void
    {
        $model = AccountViewModel::forCurrentUser();
        if (is_array($model)) {
            do_action('uc_account_view_model_ready', $model);
        }
    }

    /** @param mixed $order */
    public static function emitOrderModel($order): void
    {
        $model = OrderViewModel::fromOrder($order);
        if (is_array($model)) {
            do_action('uc_order_view_model_ready', $model, $order);
        }
    }
}
