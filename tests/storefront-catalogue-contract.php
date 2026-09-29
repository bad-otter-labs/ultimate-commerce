<?php

declare(strict_types=1);

define('ABSPATH', __DIR__ . '/');
define('ULTIMATE_COMMERCE_URL', 'https://example.test/wp-content/plugins/ultimate-commerce-for-woocommerce/');
define('ULTIMATE_COMMERCE_DIR', dirname(__DIR__) . '/packages/ultimate-commerce-for-woocommerce/');
define('ULTIMATE_COMMERCE_VERSION', '0.2.0');

$GLOBALS['uc_test_registered_scripts'] = array();
$GLOBALS['uc_test_enqueued_scripts'] = array();

function __($text, $domain = null): string { return (string) $text; }
function apply_filters($tag, $value, ...$args) { return $value; }
function do_action($tag, ...$args): void {}
function sanitize_key($value): string { return strtolower((string) preg_replace('/[^a-z0-9_\-]/i', '', (string) $value)); }
function sanitize_title($value): string { return trim(strtolower((string) preg_replace('/[^a-z0-9_\-]+/i', '-', (string) $value)), '-'); }
function sanitize_html_class($value): string { return sanitize_key($value); }
function esc_attr($value): string { return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8'); }
function esc_html($value): string { return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8'); }
function esc_url($value): string { return (string) $value; }
function wp_kses_post($value): string { return (string) $value; }
function wp_json_encode($value, $flags = 0) { return json_encode($value, $flags); }
function wp_script_is($handle, $status = 'enqueued'): bool {
    if ($status === 'registered') {
        return isset($GLOBALS['uc_test_registered_scripts'][$handle]);
    }
    return isset($GLOBALS['uc_test_enqueued_scripts'][$handle]);
}
function wp_register_script($handle, $src, $deps = array(), $version = false, $inFooter = false): bool {
    $GLOBALS['uc_test_registered_scripts'][$handle] = compact('src', 'deps', 'version', 'inFooter');
    return true;
}
function wp_enqueue_script($handle): void { $GLOBALS['uc_test_enqueued_scripts'][$handle] = true; }
function wp_set_script_translations($handle, $domain = 'default', $path = ''): bool { return true; }
function wc_format_decimal($value, $decimals = 2): string { return number_format((float) $value, (int) $decimals, '.', ''); }
function wc_get_price_decimals(): int { return 2; }
function wc_attribute_taxonomy_name($name): string { return 'pa_' . sanitize_key($name); }
function wc_get_attribute_taxonomies(): array {
    return array(
        (object) array('attribute_name' => 'colour', 'attribute_label' => 'Colour'),
        (object) array('attribute_name' => 'size', 'attribute_label' => 'Size'),
    );
}
function taxonomy_exists($taxonomy): bool { return in_array($taxonomy, array('pa_colour', 'pa_size', 'product_brand'), true); }
function is_object_in_taxonomy($object, $taxonomy): bool { return $object === 'product' && taxonomy_exists($taxonomy); }
function get_terms($args): array {
    if (($args['taxonomy'] ?? '') === 'pa_colour') {
        return array(
            (object) array('term_id' => 1, 'slug' => 'navy', 'name' => 'Navy', 'count' => 7),
            (object) array('term_id' => 2, 'slug' => 'olive', 'name' => 'Olive', 'count' => 4),
        );
    }
    if (($args['taxonomy'] ?? '') === 'pa_size') {
        return array(
            (object) array('term_id' => 3, 'slug' => 'm', 'name' => 'M', 'count' => 8),
            (object) array('term_id' => 4, 'slug' => 'l', 'name' => 'L', 'count' => 5),
        );
    }
    return array();
}
function is_wp_error($value): bool { return $value instanceof WP_Error; }
function wc_attribute_label($name, $product = null): string { return $name === 'pa_colour' ? 'Colour' : ($name === 'pa_size' ? 'Size' : $name); }
function sanitize_hex_color($value) { return preg_match('/^#[0-9a-f]{6}$/i', (string) $value) ? strtolower((string) $value) : null; }
function absint($value): int { return abs((int) $value); }
function get_option($name, $default = false) { return $default; }
function wp_get_attachment_image_src($id, $size) { return false; }
function wp_get_attachment_image_srcset($id, $size) { return false; }
function wp_get_attachment_image_sizes($id, $size) { return false; }
function get_post_meta($id, $key, $single = false) { return ''; }
function get_the_title($id): string { return ''; }

class WP_Error {}
class WC_Product {
    public function get_stock_quantity() { return null; }
    public function get_low_stock_amount() { return ''; }
}
class WC_Product_Attribute {
    public function __construct(private string $name, private array $options, private bool $taxonomy = true) {}
    public function get_variation(): bool { return true; }
    public function is_taxonomy(): bool { return $this->taxonomy; }
    public function get_name(): string { return $this->name; }
    public function get_options(): array { return $this->options; }
}
class WC_Product_Variable extends WC_Product {
    public function get_id(): int { return 10; }
    public function get_attributes(): array {
        return array(
            new WC_Product_Attribute('pa_colour', array(1, 2)),
            new WC_Product_Attribute('pa_size', array(3, 4)),
            new WC_Product_Attribute('Cut Style', array('Regular Fit', 'Relaxed Fit'), false),
        );
    }
    public function get_default_attributes(): array { return array('pa_colour' => 'navy', 'pa_size' => 'm', 'cut-style' => 'regular-fit'); }
    public function get_price(): string { return '100.00'; }
    public function get_regular_price(): string { return '120.00'; }
    public function get_sale_price(): string { return '100.00'; }
    public function get_price_html(): string { return '<span>£100</span>'; }
    public function is_on_sale(): bool { return true; }
    public function get_permalink(): string { return 'https://example.test/product/10'; }
}
class WC_Product_Variation extends WC_Product {
    private int $id;
    private array $attributes;
    private bool $stock;
    public function __construct(int $id, array $attributes, bool $stock) { $this->id = $id; $this->attributes = $attributes; $this->stock = $stock; }
    public function get_id(): int { return $this->id; }
    public function get_attributes(): array { return $this->attributes; }
    public function is_purchasable(): bool { return true; }
    public function is_in_stock(): bool { return $this->stock; }
    public function get_stock_status(): string { return $this->stock ? 'instock' : 'outofstock'; }
    public function get_stock_quantity() { return $this->stock ? 2 : 0; }
    public function get_low_stock_amount() { return 2; }
    public function get_price(): string { return $this->id === 11 ? '100.00' : '105.00'; }
    public function get_regular_price(): string { return '120.00'; }
    public function get_sale_price(): string { return $this->get_price(); }
    public function get_price_html(): string { return '<span>£' . $this->get_price() . '</span>'; }
    public function is_on_sale(): bool { return true; }
    public function get_image_id(): int { return 0; }
}

require_once __DIR__ . '/../packages/ultimate-commerce-for-woocommerce/src/Storefront/MediaViewModel.php';
require_once __DIR__ . '/../packages/ultimate-commerce-for-woocommerce/src/Storefront/CatalogFilterRegistry.php';
require_once __DIR__ . '/../packages/ultimate-commerce-for-woocommerce/src/Storefront/VariationViewModel.php';
require_once __DIR__ . '/../packages/ultimate-commerce-for-woocommerce/src/Storefront/ProductCardRenderer.php';

use BadOtter\UltimateCommerce\Storefront\CatalogFilterRegistry;
use BadOtter\UltimateCommerce\Storefront\ProductCardRenderer;
use BadOtter\UltimateCommerce\Storefront\VariationViewModel;

function uc_storefront_assert(bool $condition, string $message): void
{
    if (!$condition) {
        fwrite(STDERR, "FAIL: {$message}\n");
        exit(1);
    }
}

$state = CatalogFilterRegistry::normalize(array(
    'filter_colour' => 'navy,olive',
    'filter_size' => array('m'),
    'min_price' => '50',
    'max_price' => '250',
    'availability' => 'in_stock',
    'sort' => 'price_asc',
    'page' => 0,
    'per_page' => 999,
));
uc_storefront_assert($state['page'] === 1, 'page must be bounded');
uc_storefront_assert($state['per_page'] === 48, 'per_page must be capped at 48');
uc_storefront_assert($state['filters']['colour'] === array('navy', 'olive'), 'colour state must be normalized');
uc_storefront_assert($state['sort'] === 'price_asc', 'allowed sort must survive normalization');
$canonical = CatalogFilterRegistry::canonicalQuery($state);
uc_storefront_assert(($canonical['filter_colour'] ?? '') === 'navy,olive', 'canonical filter URL must be deterministic');
uc_storefront_assert(($canonical['availability'] ?? '') === 'in_stock', 'availability must use flat canonical state');

$parent = new WC_Product_Variable();
$navyM = new WC_Product_Variation(11, array('pa_colour' => 'navy', 'pa_size' => 'm', 'cut-style' => 'regular-fit'), true);
$oliveL = new WC_Product_Variation(12, array('pa_colour' => 'olive', 'pa_size' => 'l', 'cut-style' => 'relaxed-fit'), false);
$context = array(
    'by_product' => array(10 => array('variations' => array($navyM, $oliveL), 'variation_ids' => array(11, 12), 'truncated' => false)),
    'term_map' => array(
        'pa_colour' => array(
            'navy' => array('id' => 1, 'slug' => 'navy', 'label' => 'Navy', 'swatch' => array('color' => '#000080', 'image_id' => 0)),
            'olive' => array('id' => 2, 'slug' => 'olive', 'label' => 'Olive', 'swatch' => array('color' => '#808000', 'image_id' => 0)),
        ),
        'pa_size' => array(
            'm' => array('id' => 3, 'slug' => 'm', 'label' => 'M', 'swatch' => array('color' => '', 'image_id' => 0)),
            'l' => array('id' => 4, 'slug' => 'l', 'label' => 'L', 'swatch' => array('color' => '', 'image_id' => 0)),
        ),
    ),
    'image_ids' => array(),
    'truncated' => false,
);
$variationSource = file_get_contents(__DIR__ . '/../packages/ultimate-commerce-for-woocommerce/src/Storefront/VariationViewModel.php');
uc_storefront_assert(is_string($variationSource), 'Variation view-model source must be readable.');
uc_storefront_assert(str_contains($variationSource, "'ulticofo_swatch_color'"), 'Swatch colour meta must use the reviewer-safe canonical prefix.');
uc_storefront_assert(str_contains($variationSource, "'ulticofo_swatch_image_id'"), 'Swatch image meta must use the reviewer-safe canonical prefix.');
uc_storefront_assert(str_contains($variationSource, "'uc_swatch_color'") && str_contains($variationSource, "'uc_swatch_image_id'"), 'Legacy swatch meta must remain a read-only compatibility fallback.');

$variation = VariationViewModel::forProduct($parent, array(), $context);
uc_storefront_assert($variation['selection']['pa_colour'] === 'navy' && $variation['selection']['pa_size'] === 'm', 'default taxonomy selection must be preserved');
uc_storefront_assert($variation['selection']['cut-style'] === 'regular-fit', 'local/custom attribute keys must use Woo-compatible sanitized titles');
uc_storefront_assert($variation['selected_variation']['id'] === 11, 'default selection must resolve to the Woo variation');
uc_storefront_assert($variation['action']['enabled'] === true && $variation['action']['variation_id'] === 11, 'valid in-stock variation must hand off to Woo cart state');
uc_storefront_assert($variation['availability_complete'] === true, 'bounded complete variation set must report complete availability');
uc_storefront_assert($variation['attributes'][0]['display'] === 'color', 'colour term meta must produce colour swatches');
uc_storefront_assert($variation['attributes'][0]['a11y']['role'] === 'radiogroup', 'attribute control must expose accessible group semantics');
uc_storefront_assert($variation['attributes'][0]['options'][0]['a11y']['role'] === 'radio', 'option must expose accessible radio semantics');
uc_storefront_assert($variation['attributes'][0]['options'][1]['available'] === false, 'unavailable option must be disabled against the selected state');
uc_storefront_assert($variation['attributes'][2]['name'] === 'cut-style' && $variation['attributes'][2]['display'] === 'button', 'local/custom variation attribute must use baseline button selector state');
uc_storefront_assert($variation['attributes'][2]['options'][0]['value'] === 'regular-fit', 'local/custom matching value must remain normalized');
uc_storefront_assert($variation['attributes'][2]['options'][0]['request_value'] === 'Regular Fit', 'local/custom Woo request value must preserve its original case and spaces');

$view = array(
    'id' => 10,
    'url' => 'https://example.test/product/10',
    'name' => 'Example Jacket',
    'classes' => array('uc-product-card', 'uc-product-card--variable'),
    'brand' => null,
    'media' => array('primary' => null, 'secondary' => null),
    'rating' => null,
    'price' => array('html' => '<span>£100</span>'),
    'variation' => $variation,
    'action' => $variation['action'],
);
$markup = ProductCardRenderer::render($view);
uc_storefront_assert(str_contains($markup, 'data-uc-variation-form="1"'), 'variable card must render the public variation form contract');
uc_storefront_assert(str_contains($markup, 'name="variation_id" value="11"'), 'variable card must hand the selected variation ID to Woo');
uc_storefront_assert(str_contains($markup, 'name="attribute_pa_colour" value="navy"'), 'variable card must hand taxonomy slugs to Woo');
uc_storefront_assert(str_contains($markup, 'value="regular-fit" data-uc-attribute-input="cut-style"'), 'local/custom matching input must remain normalized for the UC controller');
uc_storefront_assert(str_contains($markup, 'name="attribute_cut-style" value="Regular Fit"'), 'native Woo form must preserve the original local/custom option value');
uc_storefront_assert(str_contains($markup, 'data-uc-request-value="Regular Fit"'), 'rendered local/custom option must expose the Woo request value');
uc_storefront_assert(str_contains($markup, 'role="radiogroup"') && str_contains($markup, 'role="radio"'), 'rendered controls must expose radio semantics');
uc_storefront_assert(str_contains($markup, 'aria-label="Example Jacket"'), 'product media link must retain an accessible name even without an image');
uc_storefront_assert(str_contains($markup, 'role="radio" aria-checked="true" aria-disabled="false" tabindex="0"'), 'selected available variation option must be the radiogroup tab stop');
uc_storefront_assert(str_contains($markup, 'aria-disabled="true" tabindex="-1"'), 'unavailable variation options must stay out of the radiogroup tab sequence');
uc_storefront_assert(str_contains($markup, 'data-uc-variation-data="1"'), 'renderer must expose bounded variation state to the UC controller');
uc_storefront_assert(isset($GLOBALS['uc_test_enqueued_scripts'][ProductCardRenderer::VARIATION_SCRIPT_HANDLE]), 'variation renderer must enqueue its reusable controller');

$truncatedContext = $context;
$truncatedContext['by_product'][10]['truncated'] = true;
$truncated = VariationViewModel::forProduct($parent, array(), $truncatedContext);
uc_storefront_assert($truncated['availability_complete'] === false, 'truncated variation data must disclose incomplete availability');
uc_storefront_assert($truncated['attributes'][0]['options'][1]['available'] === true, 'truncated state must not falsely disable unseen combinations');

fwrite(STDOUT, "Storefront catalogue contract tests passed\n");
