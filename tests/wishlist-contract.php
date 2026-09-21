<?php

declare(strict_types=1);

define('ABSPATH', __DIR__ . '/');

$GLOBALS['uc_wishlist_meta'] = array();
$GLOBALS['uc_wishlist_products'] = array(10, 11, 12);

function __($text, $domain = null): string { return (string) $text; }
function absint($value): int { return abs((int) $value); }
function sanitize_email($value): string { return strtolower(trim((string) $value)); }
function get_user_by($field, $value) {
    return $field === 'email' && $value === 'customer@example.test' ? (object) array('ID' => 1) : false;
}
function is_multisite(): bool { return false; }
function apply_filters($tag, $value, ...$args) { return $value; }
function do_action($tag, ...$args): void {}
function get_user_meta($userId, $key, $single = false) {
    return $GLOBALS['uc_wishlist_meta'][$userId][$key] ?? ($single ? '' : array());
}
function update_user_meta($userId, $key, $value): bool {
    $GLOBALS['uc_wishlist_meta'][$userId][$key] = $value;
    return true;
}
function delete_user_meta($userId, $key): bool {
    unset($GLOBALS['uc_wishlist_meta'][$userId][$key]);
    return true;
}
function wc_get_product($productId) {
    return in_array((int) $productId, $GLOBALS['uc_wishlist_products'], true)
        ? new UC_Wishlist_Test_Product()
        : false;
}

class UC_Wishlist_Test_Product
{
    public function get_status(): string { return 'publish'; }
}

class WP_Error
{
    public function __construct(
        public string $code,
        public string $message = '',
        public array $data = array()
    ) {}
}

require dirname(__DIR__) . '/packages/ultimate-commerce-for-woocommerce/src/Wishlist/WishlistStore.php';

use BadOtter\UltimateCommerce\Wishlist\WishlistStore;

function uc_wishlist_assert(bool $condition, string $message): void
{
    if (!$condition) {
        throw new RuntimeException($message);
    }
}

function uc_wishlist_read(string $path): string
{
    $contents = file_get_contents($path);
    if (!is_string($contents)) {
        throw new RuntimeException('Unable to read ' . $path);
    }
    return $contents;
}

uc_wishlist_assert(WishlistStore::ids(1) === array(), 'New wishlist must be empty.');

$added = WishlistStore::add(1, 10);
uc_wishlist_assert($added === array(10), 'Valid Woo product should be added.');

$duplicate = WishlistStore::add(1, 10);
uc_wishlist_assert($duplicate === array(10), 'Duplicate product IDs must not be stored.');

$invalid = WishlistStore::add(1, 999);
uc_wishlist_assert($invalid instanceof WP_Error && $invalid->code === 'uc_wishlist_product_invalid', 'Invalid product must fail closed.');

$merged = WishlistStore::merge(1, array(11, 999, 12, 11));
uc_wishlist_assert($merged === array(10, 11, 12), 'Merge must keep unique valid Woo product IDs only.');

$removed = WishlistStore::remove(1, 10);
uc_wishlist_assert($removed === array(11, 12), 'Remove must update the signed-in wishlist.');

$stored = $GLOBALS['uc_wishlist_meta'][1][WishlistStore::metaKey()] ?? array();
uc_wishlist_assert($stored === array(11, 12), 'Storage must contain product IDs only.');

$GLOBALS['uc_wishlist_meta'][1][WishlistStore::metaKey()] = array(11, 12, 999);
$export = WishlistStore::privacyExporter('customer@example.test', 1);
$exportValue = $export['data'][0]['data'][0]['value'] ?? '';
uc_wishlist_assert($exportValue === '11, 12, 999', 'Privacy export must include stored preference IDs even when a Woo product is stale or unavailable.');
$GLOBALS['uc_wishlist_meta'][1][WishlistStore::metaKey()] = array(11, 12);

$root = dirname(__DIR__);
$package = $root . '/packages/ultimate-commerce-for-woocommerce';
$entry = uc_wishlist_read($package . '/ultimate-commerce-for-woocommerce.php');
$config = uc_wishlist_read($package . '/config/modules.php');
$plugin = uc_wishlist_read($package . '/src/Plugin.php');
$module = uc_wishlist_read($package . '/src/Modules/Wishlist/WishlistModule.php');
$rest = uc_wishlist_read($package . '/src/Wishlist/WishlistRestController.php');
$bootstrap = uc_wishlist_read($package . '/src/Wishlist/WishlistBootstrap.php');
$store = uc_wishlist_read($package . '/src/Wishlist/WishlistStore.php');
$controls = uc_wishlist_read($package . '/src/Storefront/WishlistControls.php');
$controller = uc_wishlist_read($package . '/assets/js/wishlist.js');
$uninstall = uc_wishlist_read($package . '/src/Privacy/Uninstall.php');
$diagnostics = uc_wishlist_read($package . '/src/Support/SystemStatus.php');
$docs = uc_wishlist_read($root . '/docs/wishlist-contract-v1.md');

foreach (array(
    "define('ULTIMATE_COMMERCE_WISHLIST_API_VERSION', '1.0.0')" => $entry,
    "use BadOtter\\UltimateCommerce\\Modules\\Wishlist\\WishlistModule;" => $config,
    'WishlistModule::class' => $config,
    "return 'wishlist';" => $module,
    "RouteRegistrar::register('/wishlist'" => $rest,
    "Authorization::requireAuthenticated()" => $rest,
    "RouteRegistrar::register('/wishlist/merge'" => $rest,
    "WishlistBootstrap::hooks();" => $module,
    "add_action('wp_ajax_' . self::ACTION" => $bootstrap,
    "add_action('wp_ajax_nopriv_' . self::ACTION" => $bootstrap,
    "nocache_headers();" => $bootstrap,
    "wp_create_nonce('wp_rest')" => $bootstrap,
    "DataRetention::ACCOUNT_LIFETIME" => $store,
    "add_action('ultimate_commerce_product_card_slot'" => $controls,
    "add_shortcode('ultimate_commerce_wishlist'" => $controls,
    "array('wp-i18n')" => $controls,
    "wp_set_script_translations" => $controls,
    "localStorage.setItem(LOCAL_KEY, JSON.stringify(normalize(values)))" => $controller,
    "'uc:wishlist-updated'" => $controller,
    "wc/store/v1/products" => $controls,
    "'bootstrapUrl' => WishlistBootstrap::url()" => $controls,
    "'storageKey' =>" => $controls,
    "String(config.bootstrapUrl || '')" => $controller,
    "String(config.storageKey || 'uc_wishlist_v1')" => $controller,
    "new URL(endpoint, window.location.href)" => $controller,
    "url.searchParams.set('include'" => $controller,
    'use BadOtter\\UltimateCommerce\\Wishlist\\WishlistStore;' => $uninstall,
    "WishlistStore::metaKey()" => $uninstall,
    "'wishlist' => self::constant('ULTIMATE_COMMERCE_WISHLIST_API_VERSION')" => $diagnostics,
    'WooCommerce remains authoritative' => $docs,
) as $needle => $haystack) {
    uc_wishlist_assert(str_contains($haystack, $needle), 'Wishlist contract marker missing: ' . $needle);
}

uc_wishlist_assert(!str_contains($rest, "'user_id'"), 'Wishlist REST routes must never accept a target user ID.');
uc_wishlist_assert(!str_contains($controls, 'wp_create_nonce'), 'Cacheable wishlist frontend config must not embed a user REST nonce.');
uc_wishlist_assert(!str_contains($controls, "'loggedIn' =>"), 'Cacheable wishlist frontend config must not embed login state.');
uc_wishlist_assert(!str_contains($controls, "'nonce' =>"), 'Cacheable wishlist frontend config must not embed nonce state.');
uc_wishlist_assert(!str_contains($controller, 'JSON.stringify(products)'), 'Guest storage must not persist product snapshots.');
uc_wishlist_assert(!preg_match('/FishingClothing|fishingclothing\.co\.uk/i', $module . $store . $rest . $bootstrap . $controls . $controller . $docs), 'Store-specific code leaked into wishlist.');

print "Wishlist contract v1 validated\n";
