<?php

namespace BadOtter\UltimateCommerce\Storefront;

use BadOtter\UltimateCommerce\Rest\RouteRegistrar;
use BadOtter\UltimateCommerce\Wishlist\WishlistBootstrap;
use BadOtter\UltimateCommerce\Wishlist\WishlistStore;

defined('ABSPATH') || exit;

final class WishlistControls
{
    public const SCRIPT_HANDLE = 'ultimate-commerce-wishlist';
    public const STYLE_HANDLE = 'ultimate-commerce-wishlist';

    private static bool $configured = false;

    public static function hooks(): void
    {
        add_action('ultimate_commerce_product_card_slot', array(__CLASS__, 'productCardSlot'), 10, 3);
        add_action('woocommerce_after_add_to_cart_form', array(__CLASS__, 'singleProductToggle'), 20);
        add_shortcode('ultimate_commerce_wishlist', array(__CLASS__, 'shortcode'));
    }

    /** @param array<string, mixed> $view
     *  @param array<string, mixed> $context
     */
    public static function productCardSlot(string $slot, array $view, array $context): void
    {
        if ($slot !== 'after_title' || empty($view['id'])) {
            return;
        }

        echo self::toggle((int) $view['id'], (string) ($view['name'] ?? '')); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- toggle() escapes complete markup.
    }

    public static function singleProductToggle(): void
    {
        global $product;
        if (!is_object($product) || !method_exists($product, 'get_id')) {
            return;
        }

        $name = method_exists($product, 'get_name') ? (string) $product->get_name() : '';
        echo self::toggle((int) $product->get_id(), $name); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- toggle() escapes complete markup.
    }

    /** @param array<string, mixed> $atts */
    public static function shortcode(array $atts = array()): string
    {
        self::enqueueAssets();
        $atts = shortcode_atts(array('title' => __('Wishlist', 'ultimate-commerce-for-woocommerce')), $atts, 'ultimate_commerce_wishlist');

        ob_start();
        ?>
        <section class="uc-wishlist" data-uc-wishlist-list="1">
            <h2 class="uc-wishlist__title"><?php echo esc_html((string) $atts['title']); ?></h2>
            <p class="uc-wishlist__status" data-uc-wishlist-status="1" aria-live="polite"></p>
            <p class="uc-wishlist__empty" data-uc-wishlist-empty="1" hidden><?php echo esc_html__('Your wishlist is empty.', 'ultimate-commerce-for-woocommerce'); ?></p>
            <div class="uc-wishlist__items" data-uc-wishlist-items="1"></div>
        </section>
        <?php
        return (string) ob_get_clean();
    }

    public static function toggle(int $productId, string $productName = ''): string
    {
        if ($productId <= 0) {
            return '';
        }

        self::enqueueAssets();
        $label = __('Add to wishlist', 'ultimate-commerce-for-woocommerce');

        return '<button type="button" class="uc-wishlist-toggle" data-uc-wishlist-toggle="1" data-product-id="'
            . esc_attr((string) $productId)
            . '" data-product-name="'
            . esc_attr($productName)
            . '" aria-pressed="false"><span data-uc-wishlist-label="1">'
            . esc_html($label)
            . '</span></button>';
    }

    public static function enqueueAssets(): void
    {
        if (!wp_script_is(self::SCRIPT_HANDLE, 'registered')) {
            wp_register_script(
                self::SCRIPT_HANDLE,
                ULTIMATE_COMMERCE_URL . 'assets/js/wishlist.js',
                array('wp-i18n'),
                ULTIMATE_COMMERCE_VERSION,
                true
            );
        }
        if (!wp_style_is(self::STYLE_HANDLE, 'registered')) {
            wp_register_style(
                self::STYLE_HANDLE,
                ULTIMATE_COMMERCE_URL . 'assets/css/wishlist.css',
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
            'bootstrapUrl' => WishlistBootstrap::url(),
            'restRoot' => untrailingslashit(rest_url(RouteRegistrar::NAMESPACE . '/wishlist')),
            'storeProducts' => rest_url('wc/store/v1/products'),
            'maxItems' => WishlistStore::MAX_ITEMS,
            'storageKey' => (function_exists('is_multisite') && is_multisite() && function_exists('get_current_blog_id'))
                ? 'ulticofo_wishlist_v1_' . (int) get_current_blog_id()
                : 'ulticofo_wishlist_v1',
        );
        $json = wp_json_encode($config, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);
        if (is_string($json)) {
            wp_add_inline_script(self::SCRIPT_HANDLE, 'window.ulticofoWishlistConfig = ' . $json . ';', 'before');
        }
    }
}
