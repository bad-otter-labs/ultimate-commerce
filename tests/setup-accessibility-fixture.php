<?php

defined('ABSPATH') || exit;

function uc_a11y_fixture_assert(bool $condition, string $message): void
{
    if (!$condition) {
        throw new RuntimeException($message);
    }
}

uc_a11y_fixture_assert(class_exists('WooCommerce'), 'WooCommerce must be active before fixture setup.');
uc_a11y_fixture_assert(class_exists('WC_Product_Variable'), 'WooCommerce product CRUD is unavailable.');

$attribute = new WC_Product_Attribute();
$attribute->set_name('Colour');
$attribute->set_options(array('Red', 'Blue'));
$attribute->set_visible(true);
$attribute->set_variation(true);

$variable = new WC_Product_Variable();
$variable->set_name('Accessible Variable Product');
$variable->set_status('publish');
$variable->set_attributes(array($attribute));
$variableId = $variable->save();
uc_a11y_fixture_assert((int) $variableId > 0, 'Could not create variable accessibility product.');

foreach (
    array(
        'Red' => '21.00',
        'Blue' => '22.00',
    ) as $colour => $price
) {
    $variation = new WC_Product_Variation();
    $variation->set_parent_id((int) $variableId);
    $variation->set_attributes(array('colour' => $colour));
    $variation->set_regular_price($price);
    $variation->set_status('publish');
    $variation->set_manage_stock(true);
    $variation->set_stock_quantity(10);
    uc_a11y_fixture_assert((int) $variation->save() > 0, 'Could not create accessibility variation.');
}

$variable = wc_get_product((int) $variableId);
uc_a11y_fixture_assert($variable instanceof WC_Product_Variable, 'Variable fixture product could not be reloaded.');
WC_Product_Variable::sync((int) $variableId);
wc_delete_product_transients((int) $variableId);

$recent = new WC_Product_Simple();
$recent->set_name('Recently Viewed Accessibility Product');
$recent->set_status('publish');
$recent->set_regular_price('9.99');
$recent->set_manage_stock(true);
$recent->set_stock_quantity(10);
$recentId = $recent->save();
uc_a11y_fixture_assert((int) $recentId > 0, 'Could not create recently viewed accessibility product.');

update_option('badotulc_a11y_recent_product_id', (int) $recentId, false);

$content = sprintf(
    '[badotulc_accessibility_fixture product_id="%1$d"]' . "\n\n" .
    '[ultimate_commerce_wishlist title="Wishlist"]' . "\n\n" .
    '[ultimate_commerce_recently_viewed title="Recently viewed"]' . "\n\n" .
    '[badotulc_catalogue_fixture_link]',
    (int) $variableId
);

$pageId = wp_insert_post(
    array(
        'post_title' => 'Ultimate Commerce Accessibility Fixture',
        'post_name' => 'ultimate-commerce-accessibility-fixture',
        'post_status' => 'publish',
        'post_type' => 'page',
        'post_content' => $content,
    ),
    true
);
uc_a11y_fixture_assert(!is_wp_error($pageId) && (int) $pageId > 0, 'Could not create accessibility fixture page.');

// Disposable catalogue products exercise the actual Woo category, price,
// product-card, cart and search paths rather than only static HTML contracts.
$jackets = wp_insert_term('Catalogue Fixture Jackets', 'product_cat', array('slug' => 'uc-a11y-fixture-jackets'));
$accessories = wp_insert_term('Catalogue Fixture Accessories', 'product_cat', array('slug' => 'uc-a11y-fixture-accessories'));
uc_a11y_fixture_assert(is_array($jackets) && (int) ($jackets['term_id'] ?? 0) > 0, 'Jackets fixture category was not created.');
uc_a11y_fixture_assert(is_array($accessories) && (int) ($accessories['term_id'] ?? 0) > 0, 'Accessories fixture category was not created.');

$catalogueProducts = array(
    array('name' => 'Catalogue Fixture Jacket Red', 'price' => '15.00', 'category' => (int) $jackets['term_id']),
    array('name' => 'Catalogue Fixture Jacket Navy', 'price' => '39.00', 'category' => (int) $jackets['term_id']),
    array('name' => 'Catalogue Fixture Hat Blue', 'price' => '9.00', 'category' => (int) $accessories['term_id']),
);
$catalogueIds = array();
foreach ($catalogueProducts as $spec) {
    $product = new WC_Product_Simple();
    $product->set_name($spec['name']);
    $product->set_status('publish');
    $product->set_regular_price($spec['price']);
    $product->set_manage_stock(true);
    $product->set_stock_quantity(10);
    $product->set_category_ids(array($spec['category']));
    $id = (int) $product->save();
    uc_a11y_fixture_assert($id > 0, 'Could not create a real Woo catalogue fixture product.');
    $catalogueIds[] = $id;
}

$cataloguePageId = wp_insert_post(
    array(
        'post_title' => 'Ultimate Commerce Catalogue Fixture',
        'post_name' => 'ultimate-commerce-catalogue-fixture',
        'post_status' => 'publish',
        'post_type' => 'page',
        'post_content' => '[badotulc_catalogue_a11y_fixture]',
    ),
    true
);
uc_a11y_fixture_assert(
    !is_wp_error($cataloguePageId) && (int) $cataloguePageId > 0,
    'Could not create live Woo catalogue integration page.'
);
update_option('badotulc_a11y_catalogue_page_id', (int) $cataloguePageId, false);

// Checkout is real Woo order placement but uses only offline bank transfer;
 // never connect a live provider or collect real customer/payment details.
$checkoutPage = wp_insert_post(
    array(
        'post_title' => 'Checkout',
        'post_name' => 'checkout',
        'post_status' => 'publish',
        'post_type' => 'page',
        'post_content' => '[woocommerce_checkout]',
    ),
    true
);
uc_a11y_fixture_assert(!is_wp_error($checkoutPage) && (int) $checkoutPage > 0, 'Could not create checkout page.');
update_option('woocommerce_checkout_page_id', (int) $checkoutPage, false);
update_option('woocommerce_enable_guest_checkout', 'yes', false);
update_option('woocommerce_default_country', 'GB', false);
update_option('woocommerce_allowed_countries', 'specific', false);
update_option('woocommerce_specific_allowed_countries', array('GB'), false);
update_option('woocommerce_currency', 'GBP', false);
update_option('woocommerce_bacs_settings', array(
    'enabled' => 'yes',
    'title' => 'Direct bank transfer (fixture only)',
    'description' => 'No payment is processed in this disposable test.',
    'instructions' => '',
), false);

update_option('show_on_front', 'page');
update_option('page_on_front', (int) $pageId);

echo wp_json_encode(
    array(
        'page_id' => (int) $pageId,
        'variable_product_id' => (int) $variableId,
        'recent_product_id' => (int) $recentId,
        'catalogue_page_id' => (int) $cataloguePageId,
        'catalogue_product_ids' => $catalogueIds,
    ),
    JSON_UNESCAPED_SLASHES
) . PHP_EOL;
