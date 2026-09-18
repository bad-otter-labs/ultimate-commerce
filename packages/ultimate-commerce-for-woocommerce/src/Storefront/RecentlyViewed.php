<?php

namespace BadOtter\UltimateCommerce\Storefront;

defined('ABSPATH') || exit;

final class RecentlyViewed
{
    public const SCRIPT_HANDLE = 'ultimate-commerce-recently-viewed';
    public const STYLE_HANDLE = 'ultimate-commerce-recently-viewed';
    public const MAX_ITEMS = 12;

    private static bool $configured = false;

    public static function hooks(): void
    {
        add_action('wp_enqueue_scripts', array(__CLASS__, 'trackCurrentProduct'), 20);
        add_shortcode('ultimate_commerce_recently_viewed', array(__CLASS__, 'shortcode'));
    }

    public static function trackCurrentProduct(): void
    {
        if (!function_exists('is_product') || !is_product()) {
            return;
        }

        $productId = function_exists('get_queried_object_id') ? absint(get_queried_object_id()) : 0;
        if ($productId > 0) {
            self::enqueueAssets($productId);
        }
    }

    /** @param array<string, mixed> $atts */
    public static function shortcode(array $atts = array()): string
    {
        $atts = shortcode_atts(
            array(
                'title' => __('Recently viewed', 'ultimate-commerce-for-woocommerce'),
                'limit' => 8,
            ),
            $atts,
            'ultimate_commerce_recently_viewed'
        );

        $limit = max(1, min(self::MAX_ITEMS, absint($atts['limit'])));
        self::enqueueAssets(0);

        ob_start();
        ?>
        <section class="uc-recently-viewed" data-uc-recently-viewed-list="1" data-limit="<?php echo esc_attr((string) $limit); ?>">
            <div class="uc-recently-viewed__header">
                <h2 class="uc-recently-viewed__title"><?php echo esc_html((string) $atts['title']); ?></h2>
                <button type="button" class="uc-recently-viewed__clear" data-uc-recently-viewed-clear="1"><?php echo esc_html__('Clear', 'ultimate-commerce-for-woocommerce'); ?></button>
            </div>
            <p class="uc-recently-viewed__status" data-uc-recently-viewed-status="1" aria-live="polite"></p>
            <p class="uc-recently-viewed__empty" data-uc-recently-viewed-empty="1" hidden><?php echo esc_html__('No recently viewed products yet.', 'ultimate-commerce-for-woocommerce'); ?></p>
            <div class="uc-recently-viewed__items" data-uc-recently-viewed-items="1"></div>
        </section>
        <?php
        return (string) ob_get_clean();
    }

    public static function enqueueAssets(int $currentProductId = 0): void
    {
        if (!wp_script_is(self::SCRIPT_HANDLE, 'registered')) {
            wp_register_script(
                self::SCRIPT_HANDLE,
                ULTIMATE_COMMERCE_URL . 'assets/js/recently-viewed.js',
                array('wp-i18n'),
                ULTIMATE_COMMERCE_VERSION,
                true
            );
        }
        if (!wp_style_is(self::STYLE_HANDLE, 'registered')) {
            wp_register_style(
                self::STYLE_HANDLE,
                ULTIMATE_COMMERCE_URL . 'assets/css/recently-viewed.css',
                array(),
                ULTIMATE_COMMERCE_VERSION
            );
        }

        wp_enqueue_script(self::SCRIPT_HANDLE);
        wp_set_script_translations(self::SCRIPT_HANDLE, 'ultimate-commerce-for-woocommerce', ULTIMATE_COMMERCE_DIR . 'languages');
        wp_enqueue_style(self::STYLE_HANDLE);

        if (self::$configured) {
            return;
        }
        self::$configured = true;

        $config = array(
            'currentProductId' => max(0, $currentProductId),
            'storeProducts' => rest_url('wc/store/v1/products'),
            'maxItems' => self::MAX_ITEMS,
            'storageKey' => (function_exists('is_multisite') && is_multisite() && function_exists('get_current_blog_id'))
                ? 'uc_recently_viewed_v1_' . (int) get_current_blog_id()
                : 'uc_recently_viewed_v1',
        );
        $json = wp_json_encode($config, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);
        if (is_string($json)) {
            wp_add_inline_script(self::SCRIPT_HANDLE, 'window.ucRecentlyViewedConfig = ' . $json . ';', 'before');
        }
    }
}
