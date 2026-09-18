<?php

declare(strict_types=1);

$root = dirname(__DIR__);
$package = $root . '/packages/ultimate-commerce-for-woocommerce';

function uc_recent_read(string $path): string
{
    $contents = file_get_contents($path);
    if (!is_string($contents)) {
        throw new RuntimeException('Unable to read ' . $path);
    }
    return $contents;
}

function uc_recent_assert(bool $condition, string $message): void
{
    if (!$condition) {
        throw new RuntimeException($message);
    }
}

$entry = uc_recent_read($package . '/ultimate-commerce-for-woocommerce.php');
$config = uc_recent_read($package . '/config/modules.php');
$module = uc_recent_read($package . '/src/Modules/RecentlyViewed/RecentlyViewedModule.php');
$view = uc_recent_read($package . '/src/Storefront/RecentlyViewed.php');
$controller = uc_recent_read($package . '/assets/js/recently-viewed.js');
$diagnostics = uc_recent_read($package . '/src/Support/SystemStatus.php');
$docs = uc_recent_read($root . '/docs/recently-viewed-contract-v1.md');

foreach (array(
    "define('ULTIMATE_COMMERCE_RECENTLY_VIEWED_API_VERSION', '1.0.0')" => $entry,
    'RecentlyViewedModule::class' => $config,
    "return 'recently_viewed';" => $module,
    "add_action('wp_enqueue_scripts'" => $view,
    "add_shortcode('ultimate_commerce_recently_viewed'" => $view,
    "array('wp-i18n')" => $view,
    'wp_set_script_translations' => $view,
    "rest_url('wc/store/v1/products')" => $view,
    "'currentProductId' =>" => $view,
    "'storageKey' =>" => $view,
    "window.localStorage.setItem(LOCAL_KEY, JSON.stringify(ids))" => $controller,
    "'uc:recently-viewed-updated'" => $controller,
    "cache: 'no-store'" => $controller,
    "'recently_viewed' => self::constant('ULTIMATE_COMMERCE_RECENTLY_VIEWED_API_VERSION')" => $diagnostics,
    'WooCommerce remains authoritative' => $docs,
) as $needle => $haystack) {
    uc_recent_assert(str_contains($haystack, $needle), 'Recently viewed contract marker missing: ' . $needle);
}

foreach (array('wp_create_nonce', 'is_user_logged_in', 'get_current_user_id', 'update_user_meta', 'get_user_meta', 'register_rest_route', 'RouteRegistrar::register') as $forbidden) {
    uc_recent_assert(!str_contains($view . $controller . $module, $forbidden), 'Recently viewed must remain browser-local/cache-safe: ' . $forbidden);
}
uc_recent_assert(!str_contains($controller, 'JSON.stringify(products)'), 'Recently viewed local storage must not persist product snapshots.');
uc_recent_assert(!preg_match('/FishingClothing|fishingclothing\.co\.uk/i', $module . $view . $controller . $docs), 'Store-specific code leaked into recently viewed.');

print "Recently viewed contract v1 validated\n";
