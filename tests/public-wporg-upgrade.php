<?php
/**
 * Disposable release acceptance: published WordPress.org 0.2.0 -> candidate
 * 0.2.2 -> published 0.2.0 rollback -> candidate 0.2.2.
 *
 * Executed by wp eval-file against real installed ZIPs, WooCommerce and HPOS.
 * The fixture is intentionally not distributed in the WordPress.org ZIP.
 */

declare(strict_types=1);

use Automattic\WooCommerce\Utilities\OrderUtil;
use BadOtter\UltimateCommerce\Support\Settings;
use BadOtter\UltimateCommerce\Wishlist\WishlistStore;

defined('ABSPATH') || exit;

function uc_public_upgrade_assert(bool $condition, string $message): void
{
    if (!$condition) {
        throw new RuntimeException($message);
    }
}

$stage = (string) getenv('UC_UPGRADE_STAGE');
$versions = array(
    'seed'           => '0.2.0',
    'upgraded'       => '0.2.2',
    'rolled_back'    => '0.2.0',
    'upgraded_again' => '0.2.2',
);
uc_public_upgrade_assert(array_key_exists($stage, $versions), 'Unknown public ZIP upgrade test stage.');
$expectedVersion = $versions[$stage];

require_once ABSPATH . 'wp-admin/includes/plugin.php';
$basename = 'bad-otter-ultimate-commerce-woocommerce/ultimate-commerce-for-woocommerce.php';

uc_public_upgrade_assert(is_plugin_active($basename), 'Canonical WordPress.org Free plugin became inactive.');
uc_public_upgrade_assert(
    defined('ULTIMATE_COMMERCE_VERSION') && ULTIMATE_COMMERCE_VERSION === $expectedVersion,
    'Booted Free version does not match the exact installed archive.'
);
uc_public_upgrade_assert(
    (string) get_plugin_data(WP_PLUGIN_DIR . '/' . $basename, false, false)['Version'] === $expectedVersion,
    'WordPress plugin header does not match the expected installed archive.'
);

$copies = array_values(array_filter(
    array_keys(get_plugins()),
    static function (string $entry): bool {
        return str_ends_with($entry, '/ultimate-commerce-for-woocommerce.php')
            || $entry === 'ultimate-commerce/ultimate-commerce.php';
    }
));
uc_public_upgrade_assert(
    $copies === array($basename),
    'A duplicate/legacy Ultimate Commerce installation or changed basename was detected: ' . implode(', ', $copies)
);
uc_public_upgrade_assert(
    defined('WC_VERSION') && WC_VERSION === '11.2.2',
    'Unexpected WooCommerce release in public ZIP upgrade fixture.'
);
uc_public_upgrade_assert(
    OrderUtil::custom_orders_table_usage_is_enabled(),
    'HPOS stopped being the authoritative WooCommerce order datastore.'
);
uc_public_upgrade_assert(
    class_exists(Settings::class) && class_exists(WishlistStore::class),
    'Public module settings or wishlist API is unavailable.'
);

if ($stage === 'seed') {
    $username = 'uc_public_upgrade_customer';
    $email = 'uc-public-upgrade@example.test';
    uc_public_upgrade_assert(!username_exists($username), 'The disposable customer already exists.');
    $userId = wp_create_user($username, wp_generate_password(32, true, true), $email);
    uc_public_upgrade_assert(!is_wp_error($userId) && (int) $userId > 0, 'Could not seed WooCommerce customer.');
    $userId = (int) $userId;

    $product = new WC_Product_Simple();
    $product->set_name('Disposable WordPress.org Upgrade Product');
    $product->set_status('publish');
    $product->set_regular_price('17.50');
    $product->set_manage_stock(true);
    $product->set_stock_quantity(15);
    $productId = (int) $product->save();
    uc_public_upgrade_assert($productId > 0, 'Could not seed a real WooCommerce product.');

    $order = wc_create_order(array('customer_id' => $userId));
    uc_public_upgrade_assert($order instanceof WC_Order, 'Could not seed an HPOS order.');
    $order->add_product($product, 2);
    $order->calculate_totals();
    $order->save();
    $orderId = (int) $order->get_id();
    uc_public_upgrade_assert($orderId > 0, 'Seed HPOS order was not persisted.');

    $savedModules = Settings::updateModuleStates(array(
        'product_display' => true,
        'variations'      => true,
        'cart'            => false,
        'wishlist'        => true,
        'recently_viewed' => false,
        'account'         => true,
    ));
    uc_public_upgrade_assert($savedModules['cart'] === false, 'Disabled module preference failed to save.');
    uc_public_upgrade_assert($savedModules['wishlist'] === true, 'Enabled module preference failed to save.');

    $wishlist = WishlistStore::add($userId, $productId);
    uc_public_upgrade_assert(
        is_array($wishlist) && $wishlist === array($productId),
        'Published Free 0.2.0 could not seed a real customer wishlist.'
    );
    Settings::updateDeleteDataOnUninstall(false);
    update_option('woocommerce_store_address', '14 Disposable Test Lane', false);

    $fixture = array(
        'user_id'         => $userId,
        'product_id'      => $productId,
        'order_id'        => $orderId,
        'module_settings' => Settings::moduleStates(),
        'schema_version'  => get_option('badotulc_schema_version'),
        'store_address'   => get_option('woocommerce_store_address'),
    );
    update_option('uc_public_upgrade_fixture', $fixture, false);
} else {
    $fixture = get_option('uc_public_upgrade_fixture');
    uc_public_upgrade_assert(is_array($fixture), 'Pre-upgrade fixture was lost.');
    uc_public_upgrade_assert(
        Settings::moduleStates() === $fixture['module_settings'],
        'A module enabled/disabled setting changed during upgrade or rollback.'
    );
    uc_public_upgrade_assert(
        get_option('badotulc_schema_version') === $fixture['schema_version'],
        'Canonical Free schema option was changed or deleted.'
    );
    uc_public_upgrade_assert(
        get_option('woocommerce_store_address') === $fixture['store_address'],
        'A WooCommerce-owned setting changed during upgrade or rollback.'
    );
    uc_public_upgrade_assert(
        Settings::deleteDataOnUninstall() === false,
        'The merchant data-retention setting changed during the upgrade.'
    );
}

$userId = (int) $fixture['user_id'];
$productId = (int) $fixture['product_id'];
$orderId = (int) $fixture['order_id'];

uc_public_upgrade_assert(get_user_by('id', $userId) instanceof WP_User, 'Existing customer was lost.');
uc_public_upgrade_assert(
    WishlistStore::ids($userId) === array($productId),
    'Customer wishlist product IDs changed or disappeared.'
);
uc_public_upgrade_assert(
    get_user_meta($userId, WishlistStore::metaKey(), true) === array($productId),
    'Existing wishlist user meta was rewritten or lost.'
);
$product = wc_get_product($productId);
uc_public_upgrade_assert(
    $product instanceof WC_Product && $product->get_status() === 'publish',
    'WooCommerce product is missing or no longer published.'
);
uc_public_upgrade_assert(
    (string) $product->get_regular_price() === '17.50'
        && (int) $product->get_stock_quantity() === 15,
    'WooCommerce product price/stock changed on plugin replacement.'
);

$order = wc_get_order($orderId);
uc_public_upgrade_assert($order instanceof WC_Order, 'WooCommerce HPOS order disappeared.');
uc_public_upgrade_assert((int) $order->get_customer_id() === $userId, 'HPOS customer ownership changed.');
uc_public_upgrade_assert((string) $order->get_total() === '35.00', 'WooCommerce order total changed.');
uc_public_upgrade_assert(count($order->get_items('line_item')) === 1, 'WooCommerce order lines changed.');

global $wpdb;
$table = $wpdb->prefix . 'wc_orders';
$hposId = (int) $wpdb->get_var($wpdb->prepare("SELECT id FROM {$table} WHERE id = %d", $orderId));
uc_public_upgrade_assert($hposId === $orderId, 'WooCommerce HPOS order row is missing.');

$request = new WP_REST_Request('GET', '/wc/store/v1/products/' . $productId);
$response = rest_do_request($request);
uc_public_upgrade_assert(!$response->is_error() && $response->get_status() === 200, 'Woo Store API failed after plugin replacement.');
$data = $response->get_data();
uc_public_upgrade_assert(
    is_array($data) && (int) ($data['id'] ?? 0) === $productId,
    'Woo Store API lost the original product.'
);

if ($expectedVersion === '0.2.2') {
    uc_public_upgrade_assert(
        defined('ULTIMATE_COMMERCE_STOREFRONT_API_VERSION')
            && ULTIMATE_COMMERCE_STOREFRONT_API_VERSION === '1.1.0',
        'Free 0.2.2 did not preserve the public Storefront API 1.1 contract.'
    );
}

printf(
    "WordPress.org Free ZIP stage %s passed: plugin %s, Woo product %d, HPOS order %d, wishlist and merchant module preferences preserved.\n",
    $stage,
    $expectedVersion,
    $productId,
    $orderId
);
