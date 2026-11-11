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
