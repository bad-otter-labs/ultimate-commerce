<?php

declare(strict_types=1);

define('ABSPATH', __DIR__ . '/');

$GLOBALS['uc_account_current_user'] = 7;
$GLOBALS['uc_account_last_query'] = array();

function __($text, $domain = null): string
{
    unset($domain);
    return (string) $text;
}

function absint($value): int
{
    return abs((int) $value);
}

function apply_filters($tag, $value, ...$args)
{
    unset($tag, $args);
    return $value;
}

function get_current_user_id(): int
{
    return (int) $GLOBALS['uc_account_current_user'];
}

function is_user_logged_in(): bool
{
    return get_current_user_id() > 0;
}

function is_wp_error($value): bool
{
    return $value instanceof WP_Error;
}

function get_userdata($userId)
{
    return (int) $userId === 7 ? (object) array('ID' => 7, 'display_name' => 'Test Customer') : false;
}

function wc_get_page_permalink($page): string
{
    return $page === 'myaccount' ? 'https://example.test/my-account/' : '';
}

function wc_get_account_endpoint_url($endpoint): string
{
    return 'https://example.test/my-account/' . $endpoint . '/';
}

function wc_get_order_status_name($status): string
{
    return ucfirst((string) $status);
}

class WP_Error
{
    public function __construct(
        public string $code,
        public string $message = '',
        public array $data = array()
    ) {
    }
}

class UcAccountDate
{
    public function date(string $format): string
    {
        unset($format);
        return '2026-09-18T09:00:00+00:00';
    }
}

class WC_Order_Item_Product
{
    public function __construct(private int $productId, private string $name)
    {
    }

    public function get_product_id(): int { return $this->productId; }
    public function get_variation_id(): int { return 0; }
    public function get_name(): string { return $this->name; }
    public function get_quantity(): int { return 2; }
    public function get_subtotal(): string { return '39.98'; }
    public function get_total(): string { return '35.98'; }
}

class WC_Order
{
    public function __construct(private int $id, private int $customerId)
    {
    }

    public function get_customer_id(): int { return $this->customerId; }
    public function get_date_created(): UcAccountDate { return new UcAccountDate(); }
    public function get_items($type = 'line_item'): array
    {
        return $type === 'line_item' ? array(91 => new WC_Order_Item_Product(42, 'Test Product')) : array();
    }
    public function get_status(): string { return 'processing'; }
    public function get_id(): int { return $this->id; }
    public function get_order_number(): string { return (string) $this->id; }
    public function get_currency(): string { return 'GBP'; }
    public function get_total(): string { return '35.98'; }
    public function get_item_count(): int { return 2; }
    public function get_view_order_url(): string
    {
        return 'https://example.test/my-account/view-order/' . $this->id . '/';
    }
}

function wc_get_order($orderId)
{
    if ((int) $orderId === 501) {
        return new WC_Order(501, 7);
    }
    if ((int) $orderId === 502) {
        return new WC_Order(502, 8);
    }
    if ((int) $orderId === 503) {
        return new WC_Order(503, 0);
    }
    return false;
}

function wc_get_orders(array $args): array
{
    $GLOBALS['uc_account_last_query'] = $args;
    return array(new WC_Order(501, 7));
}

require dirname(__DIR__) . '/packages/ultimate-commerce-for-woocommerce/src/Security/Authorization.php';
require dirname(__DIR__) . '/packages/ultimate-commerce-for-woocommerce/src/Account/OrderViewModel.php';
require dirname(__DIR__) . '/packages/ultimate-commerce-for-woocommerce/src/Account/AccountViewModel.php';

use BadOtter\UltimateCommerce\Account\AccountViewModel;
use BadOtter\UltimateCommerce\Account\OrderViewModel;

function uc_account_assert(bool $condition, string $message): void
{
    if (!$condition) {
        throw new RuntimeException($message);
    }
}

$own = OrderViewModel::fromOrder(501);
uc_account_assert(is_array($own), 'Owning customer must receive an order view model.');
uc_account_assert(($own['schema'] ?? '') === 'uc.order.v1', 'Order schema must be explicit.');
uc_account_assert(($own['id'] ?? 0) === 501, 'Order ID must come from WooCommerce.');
uc_account_assert(($own['items'][0]['product_id'] ?? 0) === 42, 'Line-item product ID must come from WooCommerce.');
uc_account_assert(($own['cache']['public_cache_safe'] ?? true) === false, 'Order data must never claim public cache safety.');

$GLOBALS['uc_account_current_user'] = 8;
$forbidden = OrderViewModel::fromOrder(501);
uc_account_assert($forbidden instanceof WP_Error && $forbidden->code === 'uc_object_forbidden', 'Another customer must be denied.');

$GLOBALS['uc_account_current_user'] = 7;
$guest = OrderViewModel::fromOrder(503);
uc_account_assert($guest instanceof WP_Error && $guest->code === 'uc_object_forbidden', 'Guest-order association must fail closed.');

$account = AccountViewModel::forCurrentUser(999);
uc_account_assert(is_array($account), 'Authenticated customer must receive an account view model.');
uc_account_assert(($account['schema'] ?? '') === 'uc.account.v1', 'Account schema must be explicit.');
uc_account_assert(($account['customer']['id'] ?? 0) === 7, 'Account model must be scoped to the current customer.');
uc_account_assert(($account['recent_orders'][0]['id'] ?? 0) === 501, 'Recent orders must use the order contract.');
uc_account_assert(($GLOBALS['uc_account_last_query']['customer_id'] ?? 0) === 7, 'Woo order query must be customer-scoped.');
uc_account_assert(($GLOBALS['uc_account_last_query']['limit'] ?? 0) === AccountViewModel::MAX_RECENT_ORDERS, 'Recent-order query must remain bounded.');

$GLOBALS['uc_account_current_user'] = 0;
$anonymous = AccountViewModel::forCurrentUser();
uc_account_assert($anonymous instanceof WP_Error && $anonymous->code === 'uc_authentication_required', 'Anonymous account access must fail closed.');

$root = dirname(__DIR__);
$orderSource = file_get_contents($root . '/packages/ultimate-commerce-for-woocommerce/src/Account/OrderViewModel.php');
$accountSource = file_get_contents($root . '/packages/ultimate-commerce-for-woocommerce/src/Account/AccountViewModel.php');
$hooksSource = file_get_contents($root . '/packages/ultimate-commerce-for-woocommerce/src/Account/AccountHooks.php');
$moduleSource = file_get_contents($root . '/packages/ultimate-commerce-for-woocommerce/src/Modules/Account/AccountModule.php');
$entrySource = file_get_contents($root . '/packages/ultimate-commerce-for-woocommerce/ultimate-commerce-for-woocommerce.php');
$docs = file_get_contents($root . '/docs/account-order-contract-v1.md');

foreach (array(
    "define('ULTIMATE_COMMERCE_ACCOUNT_API_VERSION', '1.0.0')" => $entrySource,
    'Authorization::requireOwnership' => $orderSource,
    'wc_get_order(' => $orderSource,
    'wc_get_orders(' => $accountSource,
    "'uc_order_view_model'" => $orderSource,
    "'uc_account_view_model'" => $accountSource,
    "'uc_account_view_model_ready'" => $hooksSource,
    "'uc_order_view_model_ready'" => $hooksSource,
    'AccountHooks::hooks();' => $moduleSource,
    'WooCommerce remains authoritative' => $docs,
    'Guest orders deliberately fail closed' => $docs,
) as $needle => $haystack) {
    uc_account_assert(is_string($haystack) && str_contains($haystack, $needle), 'Account/order contract marker missing: ' . $needle);
}

foreach (array('wp_posts', 'wp_postmeta', 'get_post_meta(', 'WP_Query', 'get_posts(') as $forbiddenMarker) {
    uc_account_assert(
        !str_contains((string) $orderSource . (string) $accountSource, $forbiddenMarker),
        'HPOS-unsafe order access leaked into account contract: ' . $forbiddenMarker
    );
}
uc_account_assert(!str_contains((string) $orderSource, 'get_order_key'), 'Order key must not enter the public view model.');
uc_account_assert(
    !preg_match('/FishingClothing|fishingclothing\.co\.uk/i', (string) $orderSource . (string) $accountSource . (string) $hooksSource . (string) $docs),
    'Store-specific code leaked into account/order contract.'
);

echo "Ultimate Commerce account/order contract v1 validated\n";
