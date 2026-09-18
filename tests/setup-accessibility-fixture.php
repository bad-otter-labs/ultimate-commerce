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

update_option('uc_a11y_recent_product_id', (int) $recentId, false);

$content = sprintf(
    '[uc_accessibility_fixture product_id="%1$d"]' . "\n\n" .
    '[ultimate_commerce_wishlist title="Wishlist"]' . "\n\n" .
    '[ultimate_commerce_recently_viewed title="Recently viewed"]',
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

update_option('show_on_front', 'page');
update_option('page_on_front', (int) $pageId);

echo wp_json_encode(
    array(
        'page_id' => (int) $pageId,
        'variable_product_id' => (int) $variableId,
        'recent_product_id' => (int) $recentId,
    ),
    JSON_UNESCAPED_SLASHES
) . PHP_EOL;
