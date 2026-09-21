<?php

declare(strict_types=1);

$root = dirname(__DIR__);

function uc_cart_test_read(string $path): string
{
    $contents = file_get_contents($path);
    if (!is_string($contents)) {
        throw new RuntimeException('Unable to read ' . $path);
    }
    return $contents;
}

function uc_cart_assert_contains(string $needle, string $haystack, string $message): void
{
    if (!str_contains($haystack, $needle)) {
        throw new RuntimeException($message . ' Missing: ' . $needle);
    }
}

function uc_cart_assert_not_contains(string $needle, string $haystack, string $message): void
{
    if (str_contains($haystack, $needle)) {
        throw new RuntimeException($message . ' Forbidden: ' . $needle);
    }
}

$package = $root . '/packages/ultimate-commerce-for-woocommerce';
$entry = uc_cart_test_read($package . '/ultimate-commerce-for-woocommerce.php');
$module = uc_cart_test_read($package . '/src/Modules/Cart/CartModule.php');
$drawer = uc_cart_test_read($package . '/src/Storefront/CartDrawer.php');
$renderer = uc_cart_test_read($package . '/src/Storefront/ProductCardRenderer.php');
$variationController = uc_cart_test_read($package . '/assets/js/variation-controls.js');
$controller = uc_cart_test_read($package . '/assets/js/cart-drawer.js');
$style = uc_cart_test_read($package . '/assets/css/cart-drawer.css');
$docs = uc_cart_test_read($root . '/docs/cart-drawer-contract-v1.md');

uc_cart_assert_contains("define('ULTIMATE_COMMERCE_CART_API_VERSION', '1.1.0')", $entry, 'Cart API 1.1 marker is missing.');
uc_cart_assert_contains('CartDrawer::hooks();', $module, 'Free Cart module must register the public drawer.');
uc_cart_assert_contains("add_action('wp_enqueue_scripts', array(__CLASS__, 'maybeEnqueueAssets'), 20);", $drawer, 'Auto-rendered drawer assets must be enqueued before footer script printing.');
uc_cart_assert_contains('self::enqueueAssets();', $drawer, 'Auto-render asset hook must enqueue the public controller/styles.');
uc_cart_assert_contains("apply_filters('ultimate_commerce_cart_drawer_auto_render'", $drawer, 'Auto-render must be replaceable.');
uc_cart_assert_contains("apply_filters('ultimate_commerce_cart_drawer_context'", $drawer, 'Drawer context extension filter is missing.');
uc_cart_assert_contains("do_action('ultimate_commerce_cart_drawer_slot'", $drawer, 'Drawer slot action is missing.');
uc_cart_assert_contains("rest_url('wc/store/v1')", $drawer, 'Drawer must derive its API root from Woo Store API.');
uc_cart_assert_contains('role="dialog"', $drawer, 'Drawer dialog semantics are missing.');
uc_cart_assert_contains('aria-modal="true"', $drawer, 'Drawer modal semantics are missing.');
uc_cart_assert_contains('data-uc-cart-toggle="1"', $drawer, 'Public cart trigger contract is missing.');

uc_cart_assert_not_contains('wp_create_nonce', $drawer, 'Cacheable drawer HTML must not embed a Store API nonce.');
uc_cart_assert_not_contains('WC()->cart', $drawer, 'Drawer shell must not become a server-side cart truth path.');
uc_cart_assert_not_contains('localStorage', $controller, 'UC must not persist a second cart snapshot.');
uc_cart_assert_not_contains('sessionStorage', $controller, 'UC must not persist a second cart snapshot.');
uc_cart_assert_not_contains('/ultimate-commerce/v1', $controller, 'Cart mutations must not use a UC-owned cart endpoint.');

foreach (array('/cart', '/cart/add-item', '/cart/update-item', '/cart/remove-item') as $endpoint) {
    uc_cart_assert_contains($endpoint, $controller, 'Required Woo Store API cart endpoint is missing.');
}
uc_cart_assert_contains("response.headers.get('Nonce')", $controller, 'Controller must consume Woo Store API response nonces.');
uc_cart_assert_contains("headers.set('Nonce', nonce)", $controller, 'Controller must send Woo Store API nonces for mutations.');
uc_cart_assert_contains("response.headers.get('Cart-Token')", $controller, 'Controller must understand Woo Cart-Token fallback.');
uc_cart_assert_contains("credentials: 'same-origin'", $controller, 'Controller must preserve Woo customer session cookies.');
uc_cart_assert_contains("cache: 'no-store'", $controller, 'Cart requests must bypass browser HTTP caches.');
uc_cart_assert_contains("querySelectorAll('[data-uc-native-attribute-input]')", $controller, 'Store API quick add must read exact Woo request values rather than normalized matching values.');
uc_cart_assert_not_contains("querySelectorAll('[data-uc-attribute-input]')", $controller, 'Cart request construction must not read normalized-only variation inputs.');
uc_cart_assert_contains("window.location.assign(action.href)", $controller, 'Simple quick add needs native Woo fallback on network failure.');
uc_cart_assert_contains('form.submit();', $controller, 'Variable quick add needs native Woo form fallback on network failure.');
uc_cart_assert_contains("emit('uc:cart-state', { cart: cart })", $controller, 'Cart state browser event is missing.');
uc_cart_assert_contains("emit('uc:cart-updated'", $controller, 'Cart update browser event is missing.');
uc_cart_assert_contains("document.addEventListener('uc:cart-open'", $controller, 'Public cart-open browser event is missing.');
uc_cart_assert_contains("document.addEventListener('uc:cart-refresh'", $controller, 'Public cart-refresh browser event is missing.');
uc_cart_assert_contains("event.key === 'Escape'", $controller, 'Escape-to-close behavior is missing.');
uc_cart_assert_contains("event.key !== 'Tab'", $controller, 'Focus-trap behavior is missing.');
uc_cart_assert_contains('function focusInitialControl()', $controller, 'Drawer must have a deterministic initial focus helper.');
uc_cart_assert_contains('focusInitialControl();', $controller, 'Drawer must move focus into the modal synchronously.');
uc_cart_assert_contains('window.requestAnimationFrame(focusInitialControl);', $controller, 'Drawer must reinforce initial focus after paint.');
uc_cart_assert_contains("!drawer.contains(document.activeElement)", $controller, 'Drawer must recover focus if asynchronous cart loading lets it escape.');
uc_cart_assert_contains("querySelector('button[data-uc-cart-close=\"1\"]')", $controller, 'Drawer initial focus must target the focusable close button, not the overlay.');

uc_cart_assert_contains('data-uc-action="', $renderer, 'Product cards must expose simple quick-add action state.');
uc_cart_assert_contains('data-uc-variation-form="1"', $renderer, 'Product cards must preserve variable form fallback markup.');
uc_cart_assert_contains('data-uc-native-attribute-input=', $renderer, 'Renderer must expose exact Woo variation request inputs.');
uc_cart_assert_contains('data-uc-request-value=', $renderer, 'Variation options must retain exact Woo request values.');
uc_cart_assert_contains("nativeInput.value = option.getAttribute( 'data-uc-request-value' )", $variationController, 'Variation controller must keep the native Woo request input synchronized.');
uc_cart_assert_contains('option.tabIndex = option === tabStop ? 0 : -1;', $variationController, 'Variation radios must use a roving tab stop.');
uc_cart_assert_contains('.uc-cart-drawer[hidden]', $style, 'Drawer hidden-state CSS is missing.');
uc_cart_assert_contains('WooCommerce remains authoritative', $docs, 'Cart ownership boundary must be documented.');
uc_cart_assert_contains('ultimate_commerce_cart_drawer_slot', $docs, 'Pro/third-party drawer slot must be documented.');
uc_cart_assert_contains('uc:cart-state', $docs, 'Initial/mutation cart-state event must be documented.');

print "Cart drawer contract v1 validated\n";
