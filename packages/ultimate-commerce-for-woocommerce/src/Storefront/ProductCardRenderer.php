<?php

namespace BadOtter\UltimateCommerce\Storefront;

defined('ABSPATH') || exit;

final class ProductCardRenderer
{
    public const VARIATION_SCRIPT_HANDLE = 'ultimate-commerce-variation-controls';

    /** @param int|\WC_Product|array<string, mixed> $product */
    public static function render($product, array $context = array()): string
    {
        $view = is_array($product) ? $product : ProductCardViewModel::fromProduct($product);
        if (empty($view['id']) || empty($view['url'])) {
            return '';
        }

        $classes = implode(' ', array_map('sanitize_html_class', (array) ($view['classes'] ?? array('uc-product-card'))));
        ob_start();
        do_action('uc_product_card_render_before', $view, $context);
        echo '<article class="' . esc_attr($classes) . '" data-uc-product-card="1" data-product-id="' . esc_attr((string) $view['id']) . '">';
        self::slot('before_media', $view, $context);
        self::media($view);
        self::slot('after_media', $view, $context);
        echo '<div class="uc-product-card__body">';
        if (!empty($view['brand']['name'])) {
            echo '<div class="uc-product-card__brand">' . esc_html((string) $view['brand']['name']) . '</div>';
        }
        self::slot('before_title', $view, $context);
        echo '<h3 class="uc-product-card__title"><a href="' . esc_url((string) $view['url']) . '">' . esc_html((string) $view['name']) . '</a></h3>';
        self::slot('after_title', $view, $context);
        if (!empty($view['rating'])) {
            $ratingLabel = sprintf(
                /* translators: 1: Average product rating, 2: Number of product reviews. */
                __('Rated %1$s out of 5 from %2$d reviews.', 'ultimate-commerce-for-woocommerce'),
                (string) $view['rating']['average'],
                (int) $view['rating']['count']
            );
            echo '<div class="uc-product-card__rating" aria-label="' . esc_attr($ratingLabel) . '">' . esc_html((string) $view['rating']['average']) . '/5</div>';
        }
        echo '<div class="uc-product-card__price" data-uc-price="1">' . wp_kses_post((string) ($view['price']['html'] ?? '')) . '</div>';
        self::slot('after_price', $view, $context);
        self::slot('before_action', $view, $context);
        if (!empty($view['variation']['variable'])) {
            self::enqueueAssets();
            echo self::renderVariationForm($view); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- helper escapes fields and JSON is hex encoded.
        } else {
            self::action($view);
        }
        self::slot('after_action', $view, $context);
        echo '</div></article>';
        do_action('uc_product_card_render_after', $view, $context);
        return (string) ob_get_clean();
    }

    /**
     * Enqueue the public progressive-enhancement controller used by UC semantic
     * variation markup. Custom renderers may call this method when adopting the
     * same data attributes/markup contract.
     */
    public static function enqueueAssets(): void
    {
        if (!function_exists('wp_register_script') || !function_exists('wp_enqueue_script')) {
            return;
        }
        if (!wp_script_is(self::VARIATION_SCRIPT_HANDLE, 'registered')) {
            wp_register_script(
                self::VARIATION_SCRIPT_HANDLE,
                ULTIMATE_COMMERCE_URL . 'assets/js/variation-controls.js',
                array('wp-i18n'),
                ULTIMATE_COMMERCE_VERSION,
                true
            );
        }
        wp_enqueue_script(self::VARIATION_SCRIPT_HANDLE);
        wp_set_script_translations(self::VARIATION_SCRIPT_HANDLE, 'ultimate-commerce-for-woocommerce', ULTIMATE_COMMERCE_DIR . 'languages');
    }

    private static function media(array $view): void
    {
        $primary = $view['media']['primary'] ?? null;
        $secondary = $view['media']['secondary'] ?? null;
        $mediaLabel = (string) ($view['name'] ?? __('View product', 'ultimate-commerce-for-woocommerce'));
        echo '<a class="uc-product-card__media" href="' . esc_url((string) $view['url']) . '" aria-label="' . esc_attr($mediaLabel) . '">';
        if (is_array($primary) && !empty($primary['src'])) {
            echo '<img class="uc-product-card__image uc-product-card__image--primary" src="' . esc_url((string) $primary['src']) . '" alt="' . esc_attr((string) ($primary['alt'] ?? '')) . '" loading="lazy"';
            if (!empty($primary['srcset'])) {
                echo ' srcset="' . esc_attr((string) $primary['srcset']) . '"';
            }
            if (!empty($primary['sizes'])) {
                echo ' sizes="' . esc_attr((string) $primary['sizes']) . '"';
            }
            echo '>';
        }
        if (is_array($secondary) && !empty($secondary['src'])) {
            echo '<img class="uc-product-card__image uc-product-card__image--secondary" src="' . esc_url((string) $secondary['src']) . '" alt="" loading="lazy" aria-hidden="true">';
        }
        echo '</a>';
    }

    private static function action(array $view): void
    {
        $action = (array) ($view['action'] ?? array());
        $label = (string) ($action['label'] ?? __('View product', 'ultimate-commerce-for-woocommerce'));
        $url = (string) ($action['url'] ?? $view['url']);
        echo '<a class="uc-product-card__action" href="' . esc_url($url) . '"';
        if (!empty($action['aria_label'])) {
            echo ' aria-label="' . esc_attr((string) $action['aria_label']) . '"';
        }
        echo ' data-uc-action="' . esc_attr((string) ($action['type'] ?? 'view_product')) . '">' . esc_html($label) . '</a>';
    }

    private static function renderVariationForm(array $view): string
    {
        $state = (array) ($view['variation'] ?? array());
        $action = (array) ($state['action'] ?? array());
        $enabled = !empty($action['enabled']);
        $payload = array(
            'product_id' => (int) $view['id'],
            'variations' => array_values((array) ($state['variations'] ?? array())),
            'availability_complete' => !empty($state['availability_complete']),
            'labels' => array(
                'add_to_cart' => __('Add to cart', 'ultimate-commerce-for-woocommerce'),
                'select_options' => __('Select options', 'ultimate-commerce-for-woocommerce'),
                'unavailable' => __('Unavailable', 'ultimate-commerce-for-woocommerce'),
            ),
        );
        $json = wp_json_encode($payload, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);
        if (!is_string($json)) {
            $json = '{}';
        }

        ob_start();
        echo '<form class="uc-product-card__variation-form cart" method="post" action="' . esc_url((string) $view['url']) . '" data-uc-variation-form="1">';
        echo '<div class="uc-variation-controls" data-uc-variation-controls="1">';
        foreach ((array) ($state['attributes'] ?? array()) as $attribute) {
            $attributeName = sanitize_key((string) ($attribute['name'] ?? ''));
            if ($attributeName === '') {
                continue;
            }
            $selectedValue = (string) ($attribute['selected'] ?? '');
            $requestValue = $selectedValue;
            echo '<div class="uc-variation-control" role="radiogroup" aria-label="' . esc_attr((string) ($attribute['a11y']['label'] ?? $attribute['label'] ?? '')) . '" data-uc-attribute="' . esc_attr($attributeName) . '">';
            echo '<span class="uc-variation-control__label">' . esc_html((string) ($attribute['label'] ?? '')) . '</span>';
            $tabStopValue = '';
            foreach ((array) ($attribute['options'] ?? array()) as $candidate) {
                if (empty($candidate['available'])) {
                    continue;
                }
                $candidateValue = (string) ($candidate['value'] ?? '');
                if (!empty($candidate['selected'])) {
                    $tabStopValue = $candidateValue;
                    break;
                }
                if ($tabStopValue === '') {
                    $tabStopValue = $candidateValue;
                }
            }

            foreach ((array) ($attribute['options'] ?? array()) as $option) {
                $optionValue = (string) ($option['value'] ?? '');
                $optionRequestValue = (string) ($option['request_value'] ?? $optionValue);
                if (!empty($option['selected'])) {
                    $requestValue = $optionRequestValue;
                }
                $tabIndex = $optionValue !== '' && $optionValue === $tabStopValue ? '0' : '-1';
                echo '<button type="button" class="uc-variation-option uc-variation-option--' . esc_attr((string) ($attribute['display'] ?? 'button')) . '" role="radio" aria-checked="' . esc_attr((string) ($option['a11y']['aria_checked'] ?? 'false')) . '" aria-disabled="' . esc_attr((string) ($option['a11y']['aria_disabled'] ?? 'false')) . '" tabindex="' . esc_attr($tabIndex) . '" data-uc-option="' . esc_attr($optionValue) . '" data-uc-request-value="' . esc_attr($optionRequestValue) . '"';
                if (!empty($option['swatch']['color'])) {
                    echo ' data-uc-swatch-color="' . esc_attr((string) $option['swatch']['color']) . '"';
                }
                if (!empty($option['swatch']['image']['src'])) {
                    echo ' data-uc-swatch-image="' . esc_url((string) $option['swatch']['image']['src']) . '"';
                }
                if (empty($option['available'])) {
                    echo ' disabled';
                }
                echo '><span class="uc-variation-option__label">' . esc_html((string) ($option['label'] ?? '')) . '</span></button>';
            }
            echo '</div>';
            echo '<input type="hidden" value="' . esc_attr($selectedValue) . '" data-uc-attribute-input="' . esc_attr($attributeName) . '">';
            echo '<input type="hidden" name="attribute_' . esc_attr($attributeName) . '" value="' . esc_attr($requestValue) . '" data-uc-native-attribute-input="' . esc_attr($attributeName) . '">';
        }
        echo '</div>';
        echo '<input type="hidden" name="quantity" value="1">';
        echo '<input type="hidden" name="add-to-cart" value="' . esc_attr((string) $view['id']) . '">';
        echo '<input type="hidden" name="product_id" value="' . esc_attr((string) $view['id']) . '">';
        echo '<input type="hidden" name="variation_id" value="' . esc_attr((string) ($action['variation_id'] ?? 0)) . '">';
        echo '<button type="submit" class="uc-product-card__action button" data-uc-variation-submit="1"' . ($enabled ? '' : ' disabled') . '>' . esc_html((string) ($action['label'] ?? __('Select options', 'ultimate-commerce-for-woocommerce'))) . '</button>';
        echo '<script type="application/json" data-uc-variation-data="1">' . $json . '</script>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- JSON_HEX_* encoding prevents HTML/script injection.
        echo '</form>';
        return (string) ob_get_clean();
    }

    private static function slot(string $slot, array $view, array $context): void
    {
        echo '<div class="uc-product-card__slot uc-product-card__slot--' . esc_attr(sanitize_html_class($slot)) . '" data-uc-slot="' . esc_attr($slot) . '">';
        do_action('uc_product_card_slot', $slot, $view, $context);
        echo '</div>';
    }
}
