<?php

namespace BadOtter\UltimateCommerce\Storefront;

defined('ABSPATH') || exit;

final class CartDrawer
{
    public const SCRIPT_HANDLE = 'ultimate-commerce-cart-drawer';
    public const STYLE_HANDLE = 'ultimate-commerce-cart-drawer';

    private static bool $rendered = false;

    public static function hooks(): void
    {
        add_action('wp_enqueue_scripts', array(__CLASS__, 'maybeEnqueueAssets'), 20);
        add_action('wp_footer', array(__CLASS__, 'maybeRender'), 30);
    }

    public static function maybeEnqueueAssets(): void
    {
        if (is_admin() || !(bool) apply_filters('uc_cart_drawer_auto_render', true)) {
            return;
        }

        self::enqueueAssets();
    }

    public static function maybeRender(): void
    {
        if (is_admin() || !(bool) apply_filters('uc_cart_drawer_auto_render', true)) {
            return;
        }

        echo self::render(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- render() escapes the complete shell.
    }

    /** @param array<string, mixed> $context */
    public static function render(array $context = array()): string
    {
        if (self::$rendered) {
            return '';
        }

        self::$rendered = true;
        self::enqueueAssets();

        $context = apply_filters('uc_cart_drawer_context', $context);
        $context = is_array($context) ? $context : array();
        $apiRoot = untrailingslashit(rest_url('wc/store/v1'));
        $drawerId = 'uc-cart-drawer';
        $titleId = $drawerId . '-title';

        ob_start();
        do_action('uc_cart_drawer_render_before', $context);
        ?>
        <div
            id="<?php echo esc_attr($drawerId); ?>"
            class="uc-cart-drawer"
            data-uc-cart-drawer="1"
            data-store-api-root="<?php echo esc_url($apiRoot); ?>"
            aria-busy="false"
            hidden
        >
            <div class="uc-cart-drawer__overlay" data-uc-cart-close="1" aria-hidden="true"></div>
            <section
                class="uc-cart-drawer__panel"
                role="dialog"
                aria-modal="true"
                aria-labelledby="<?php echo esc_attr($titleId); ?>"
                tabindex="-1"
                data-uc-cart-panel="1"
            >
                <header class="uc-cart-drawer__header">
                    <h2 id="<?php echo esc_attr($titleId); ?>" class="uc-cart-drawer__title"><?php echo esc_html__('Your cart', 'ultimate-commerce-for-woocommerce'); ?></h2>
                    <button type="button" class="uc-cart-drawer__close" data-uc-cart-close="1" aria-label="<?php echo esc_attr__('Close cart', 'ultimate-commerce-for-woocommerce'); ?>">&times;</button>
                </header>

                <p class="uc-cart-drawer__status" data-uc-cart-status="1" aria-live="polite"></p>
                <div class="uc-cart-drawer__error" data-uc-cart-error="1" role="alert" hidden></div>

                <?php self::slot('before_items', $context); ?>
                <div class="uc-cart-drawer__items" data-uc-cart-items="1"></div>
                <p class="uc-cart-drawer__empty" data-uc-cart-empty="1" hidden><?php echo esc_html__('Your cart is empty.', 'ultimate-commerce-for-woocommerce'); ?></p>
                <?php self::slot('after_items', $context); ?>

                <div class="uc-cart-drawer__summary" data-uc-cart-summary="1" hidden>
                    <?php self::slot('before_totals', $context); ?>
                    <div class="uc-cart-drawer__total-row">
                        <span><?php echo esc_html__('Total', 'ultimate-commerce-for-woocommerce'); ?></span>
                        <strong data-uc-cart-total="1"></strong>
                    </div>
                    <?php self::slot('after_totals', $context); ?>
                </div>

                <?php self::slot('before_footer', $context); ?>
                <footer class="uc-cart-drawer__footer">
                    <a class="uc-cart-drawer__cart-link" href="<?php echo esc_url(wc_get_cart_url()); ?>"><?php echo esc_html__('View cart', 'ultimate-commerce-for-woocommerce'); ?></a>
                    <a class="uc-cart-drawer__checkout button" href="<?php echo esc_url(wc_get_checkout_url()); ?>" data-uc-cart-checkout="1"><?php echo esc_html__('Checkout', 'ultimate-commerce-for-woocommerce'); ?></a>
                </footer>
                <?php self::slot('after_footer', $context); ?>
            </section>
        </div>
        <?php
        do_action('uc_cart_drawer_render_after', $context);
        return (string) ob_get_clean();
    }

    /**
     * Render an accessible cart-drawer trigger for themes or extensions.
     *
     * @param array<string, mixed> $args
     */
    public static function trigger(array $args = array()): string
    {
        $label = isset($args['label']) && is_scalar($args['label'])
            ? (string) $args['label']
            : __('Cart', 'ultimate-commerce-for-woocommerce');
        $class = isset($args['class']) && is_scalar($args['class'])
            ? sanitize_html_class((string) $args['class'])
            : 'uc-cart-trigger';

        return sprintf(
            '<button type="button" class="%1$s" data-uc-cart-toggle="1" aria-controls="uc-cart-drawer" aria-expanded="false">%2$s <span class="uc-cart-trigger__count" data-uc-cart-count="1" aria-live="polite"></span></button>',
            esc_attr($class),
            esc_html($label)
        );
    }

    public static function enqueueAssets(): void
    {
        if (!wp_script_is(self::SCRIPT_HANDLE, 'registered')) {
            wp_register_script(
                self::SCRIPT_HANDLE,
                ULTIMATE_COMMERCE_URL . 'assets/js/cart-drawer.js',
                array('wp-i18n'),
                ULTIMATE_COMMERCE_VERSION,
                true
            );
        }
        if (!wp_style_is(self::STYLE_HANDLE, 'registered')) {
            wp_register_style(
                self::STYLE_HANDLE,
                ULTIMATE_COMMERCE_URL . 'assets/css/cart-drawer.css',
                array(),
                ULTIMATE_COMMERCE_VERSION
            );
        }

        wp_enqueue_script(self::SCRIPT_HANDLE);
        wp_set_script_translations(self::SCRIPT_HANDLE, 'ultimate-commerce-for-woocommerce', ULTIMATE_COMMERCE_DIR . 'languages');
        wp_enqueue_style(self::STYLE_HANDLE);
    }

    /** @param array<string, mixed> $context */
    private static function slot(string $slot, array $context): void
    {
        echo '<div class="uc-cart-drawer__slot uc-cart-drawer__slot--' . esc_attr(sanitize_html_class($slot)) . '" data-uc-cart-slot="' . esc_attr($slot) . '">';
        do_action('uc_cart_drawer_slot', $slot, $context);
        echo '</div>';
    }
}
