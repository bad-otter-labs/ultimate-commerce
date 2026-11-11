<?php

defined('ABSPATH') || exit;

add_shortcode(
    'badotulc_accessibility_fixture',
    static function (array $atts = array()): string {
        $atts = shortcode_atts(array('product_id' => 0), $atts, 'badotulc_accessibility_fixture');
        $productId = absint($atts['product_id']);
        $recentProductId = absint(get_option('badotulc_a11y_recent_product_id', 0));

        if (
            $productId <= 0
            || !class_exists(\BadOtter\UltimateCommerce\Storefront\CartDrawer::class)
            || !class_exists(\BadOtter\UltimateCommerce\Storefront\ProductCardRenderer::class)
        ) {
            return '';
        }

        $trigger = \BadOtter\UltimateCommerce\Storefront\CartDrawer::trigger(
            array(
                'label' => 'Open cart',
                'class' => 'uc-a11y-cart-trigger',
            )
        );
        $card = \BadOtter\UltimateCommerce\Storefront\ProductCardRenderer::render(
            $productId,
            array('accessibility_fixture' => true)
        );

        return sprintf(
            '<div data-uc-a11y-fixture="1" data-recent-product-id="%1$d">%2$s%3$s</div>',
            $recentProductId,
            $trigger,
            $card
        );
    }
);


// Test-only extension seam: a merchant theme can expose Woo's native
// product categories without creating a second taxonomy or product index.
add_filter(
    'ultimate_commerce_catalog_filter_definitions',
    static function ($definitions): array {
        if (!is_array($definitions)) {
            return array();
        }
        $definitions['category'] = array(
            'id' => 'category',
            'label' => 'Category',
            'type' => 'taxonomy',
            'taxonomy' => 'product_cat',
            'param' => 'filter_category',
        );
        return $definitions;
    }
);

add_shortcode(
    'badotulc_catalogue_fixture_link',
    static function (): string {
        $pageId = (int) get_option('badotulc_a11y_catalogue_page_id', 0);
        $url = $pageId > 0 ? get_permalink($pageId) : '';
        if (!is_string($url) || $url === '') {
            return '';
        }
        return '<a data-uc-catalogue-fixture-link="1" href="' . esc_url($url) . '">Open catalogue fixture</a>';
    }
);

add_shortcode(
    'badotulc_catalogue_a11y_fixture',
    static function (): string {
        $catalogue = \BadOtter\UltimateCommerce\Storefront\Catalog::class;
        $drawer = \BadOtter\UltimateCommerce\Storefront\CartDrawer::class;
        $pageId = (int) get_option('badotulc_a11y_catalogue_page_id', 0);
        $url = $pageId > 0 ? get_permalink($pageId) : '';
        if (!class_exists($catalogue) || !is_string($url) || $url === '') {
            return '';
        }

        $query = isset($_GET) && is_array($_GET) ? wp_unslash($_GET) : array();
        $trigger = class_exists($drawer) ? $drawer::trigger(array(
            'label' => 'Open cart',
            'class' => 'uc-a11y-catalogue-cart-trigger',
        )) : '';

        $list = $catalogue::renderList(
            $query,
            array(
                'pagination_base_url' => $url,
                'pagination_query_key' => 'uc_catalog_page',
                'show_filter_controls' => true,
                'show_sort_control' => true,
                'accessibility_fixture' => true,
            )
        );
        return '<section data-uc-catalogue-fixture="1" aria-label="Catalogue test">'
            . '<h2>WooCommerce catalogue journey</h2>'
            . $trigger
            . $list
            . '</section>';
    }
);


// Disposable browser acceptance only: read back a Woo-persisted order without
// exposing a public production endpoint or requiring an admin login. The route
// exists only when this repository's test MU plugin is installed.
add_action(
    'rest_api_init',
    static function (): void {
        register_rest_route(
            'uc-fixture/v1',
            '/order-proof',
            array(
                'methods' => 'GET',
                'permission_callback' => static function (): bool {
                    return defined('UC_A11Y_CHECKOUT_TEST') && UC_A11Y_CHECKOUT_TEST === true;
                },
                'callback' => static function (\\WP_REST_Request $request) {
                    $id = absint($request->get_param('order_id'));
                    $order = $id > 0 ? wc_get_order($id) : false;
                    if (!$order || $order->get_billing_email() !== 'uc-checkout@example.test') {
                        return new \\WP_Error('not_found', 'Fixture order not found.', array('status' => 404));
                    }
                    $items = $order->get_items('line_item');
                    $item = reset($items);
                    if (!$item instanceof \\WC_Order_Item_Product) {
                        return new \\WP_Error('invalid_order', 'No fixture product.', array('status' => 404));
                    }
                    return rest_ensure_response(array(
                        'id' => (int) $order->get_id(),
                        'product_id' => (int) $item->get_product_id(),
                        'variation_id' => (int) $item->get_variation_id(),
                        'quantity' => (int) $item->get_quantity(),
                        'payment_method' => (string) $order->get_payment_method(),
                        'total_minor' => (int) round(((float) $order->get_total()) * 100),
                    ));
                },
            )
        );
    }
);
