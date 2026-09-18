<?php

declare(strict_types=1);

use Automattic\WooCommerce\Utilities\FeaturesUtil;
use Automattic\WooCommerce\Utilities\OrderUtil;
use BadOtter\UltimateCommerce\Account\AccountViewModel;
use BadOtter\UltimateCommerce\Account\OrderViewModel;

defined('ABSPATH') || exit;

function uc_live_compat_assert(bool $condition, string $message): void
{
    if (!$condition) {
        throw new RuntimeException($message);
    }
}

$expectedWordPress = (string) getenv('UC_EXPECT_WORDPRESS');
$expectedWooCommerce = (string) getenv('UC_EXPECT_WOOCOMMERCE');
$expectedPhpPrefix = (string) getenv('UC_EXPECT_PHP');
$pluginBasename = 'ultimate-commerce-for-woocommerce/ultimate-commerce-for-woocommerce.php';

global $wp_version, $wpdb;

uc_live_compat_assert((string) $wp_version === $expectedWordPress, 'Unexpected WordPress version: ' . (string) $wp_version);
uc_live_compat_assert(defined('WC_VERSION') && (string) WC_VERSION === $expectedWooCommerce, 'Unexpected WooCommerce version.');
uc_live_compat_assert(str_starts_with(PHP_VERSION, $expectedPhpPrefix . '.'), 'Unexpected PHP version: ' . PHP_VERSION);
uc_live_compat_assert(
    defined('ULTIMATE_COMMERCE_VERSION') && ULTIMATE_COMMERCE_VERSION === '0.2.0',
    'Ultimate Commerce did not boot at the expected version.'
);

if (!function_exists('is_plugin_active')) {
    require_once ABSPATH . 'wp-admin/includes/plugin.php';
}
uc_live_compat_assert(is_plugin_active($pluginBasename), 'Canonical Ultimate Commerce plugin is not active.');

$features = FeaturesUtil::get_compatible_features_for_plugin($pluginBasename);
$compatible = array_values((array) ($features['compatible'] ?? array()));
uc_live_compat_assert(in_array('custom_order_tables', $compatible, true), 'WooCommerce does not report HPOS compatibility.');
uc_live_compat_assert(in_array('cart_checkout_blocks', $compatible, true), 'WooCommerce does not report Cart/Checkout Blocks compatibility.');
uc_live_compat_assert(OrderUtil::custom_orders_table_usage_is_enabled(), 'HPOS is not the active WooCommerce order datastore.');

$ordersTable = $wpdb->prefix . 'wc_orders';
$tableExists = $wpdb->get_var($wpdb->prepare('SHOW TABLES LIKE %s', $ordersTable));
uc_live_compat_assert($tableExists === $ordersTable, 'WooCommerce HPOS orders table is missing.');

$username = 'uc_matrix_customer';
$email = 'uc-matrix-customer@example.test';
$userId = username_exists($username);
if (!$userId) {
    $userId = wp_create_user($username, wp_generate_password(24, true, true), $email);
}
uc_live_compat_assert(!is_wp_error($userId) && (int) $userId > 0, 'Could not create matrix customer.');
$userId = (int) $userId;
wp_set_current_user($userId);

$product = new WC_Product_Simple();
$product->set_name('Ultimate Commerce Matrix Product');
$product->set_status('publish');
$product->set_regular_price('19.99');
$product->set_manage_stock(true);
$product->set_stock_quantity(20);
$productId = $product->save();
uc_live_compat_assert((int) $productId > 0, 'Could not create WooCommerce product.');

$order = wc_create_order(array('customer_id' => $userId));
uc_live_compat_assert($order instanceof WC_Order, 'Could not create WooCommerce order.');
$order->add_product($product, 2);
$order->calculate_totals();
$order->save();
$orderId = (int) $order->get_id();
uc_live_compat_assert($orderId > 0, 'Created WooCommerce order has no ID.');

$hposRow = $wpdb->get_var($wpdb->prepare("SELECT id FROM {$ordersTable} WHERE id = %d", $orderId));
uc_live_compat_assert((int) $hposRow === $orderId, 'Created order was not persisted in HPOS.');

$reloaded = wc_get_order($orderId);
uc_live_compat_assert($reloaded instanceof WC_Order, 'WooCommerce CRUD could not reload HPOS order.');
uc_live_compat_assert((int) $reloaded->get_customer_id() === $userId, 'Reloaded order lost customer ownership.');
uc_live_compat_assert((string) $reloaded->get_total() === '39.98', 'Reloaded order total is unexpected.');

$orderModel = OrderViewModel::fromOrder($orderId);
uc_live_compat_assert(is_array($orderModel), 'Ultimate Commerce order view model failed for HPOS order.');
uc_live_compat_assert(($orderModel['schema'] ?? '') === 'uc.order.v1', 'Unexpected UC order schema.');
uc_live_compat_assert((int) ($orderModel['id'] ?? 0) === $orderId, 'UC order view model returned the wrong order.');
uc_live_compat_assert(
    (int) ($orderModel['items'][0]['product_id'] ?? 0) === (int) $productId,
    'UC order view model did not preserve the Woo line item.'
);

$accountModel = AccountViewModel::forCurrentUser(5);
uc_live_compat_assert(is_array($accountModel), 'Ultimate Commerce account view model failed.');
uc_live_compat_assert(($accountModel['schema'] ?? '') === 'uc.account.v1', 'Unexpected UC account schema.');
$recentIds = array_map(
    static fn(array $model): int => (int) ($model['id'] ?? 0),
    array_filter((array) ($accountModel['recent_orders'] ?? array()), 'is_array')
);
uc_live_compat_assert(in_array($orderId, $recentIds, true), 'UC account model did not include the new Woo order.');

$request = new WP_REST_Request('GET', '/wc/store/v1/products/' . (int) $productId);
$response = rest_do_request($request);
uc_live_compat_assert(!$response->is_error(), 'Woo Store API product request returned a REST error.');
uc_live_compat_assert($response->get_status() === 200, 'Woo Store API product request did not return HTTP 200.');
$storeProduct = $response->get_data();
uc_live_compat_assert(
    is_array($storeProduct) && (int) ($storeProduct['id'] ?? 0) === (int) $productId,
    'Woo Store API did not return the created product.'
);

echo sprintf(
    "Ultimate Commerce live compatibility passed: WordPress %s / WooCommerce %s / PHP %s / HPOS / Blocks declaration / Store API.\n",
    (string) $wp_version,
    (string) WC_VERSION,
    PHP_VERSION
);
