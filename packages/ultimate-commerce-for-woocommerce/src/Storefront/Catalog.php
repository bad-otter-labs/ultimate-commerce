<?php

namespace BadOtter\UltimateCommerce\Storefront;

defined('ABSPATH') || exit;

final class Catalog
{
    /** @return array<string, mixed>|\WP_Error */
    public static function query(array $args = array())
    {
        return CatalogQuery::query($args);
    }

    /**
     * @param int|\WC_Product $product
     * @return array<string, mixed>
     */
    public static function product($product): array
    {
        return ProductCardViewModel::fromProduct($product);
    }

    /** @param int|\WC_Product|array<string, mixed> $product */
    public static function renderCard($product, array $context = array()): string
    {
        return ProductCardRenderer::render($product, $context);
    }

    public static function renderList(array $args = array(), array $context = array()): string
    {
        $result = self::query($args);
        if ($result instanceof \WP_Error) {
            return '';
        }

        ob_start();
        do_action('ultimate_commerce_product_list_before', $result, $context);
        echo '<div class="uc-product-list" data-uc-product-list="1">';
        $items = (array) ($result['items'] ?? array());
        if ($items === array()) {
            $state = (array) ($result['state'] ?? array());
            $search = (string) ($state['search'] ?? '');
            if ($search !== '') {
                $message = sprintf(
                    /* translators: %s: The shopper's search term. */
                    __('No products found for "%s". Try another search or change your filters.', 'bad-otter-ultimate-commerce-woocommerce'),
                    $search
                );
            } elseif (!empty($state['selected_count'])) {
                $message = __('No products match these filters. Try removing a filter.', 'bad-otter-ultimate-commerce-woocommerce');
            } else {
                $message = __('No products are available to display.', 'bad-otter-ultimate-commerce-woocommerce');
            }
            echo '<p class="uc-product-list__empty" data-uc-catalog-empty="1" role="status">' . esc_html($message) . '</p>';

            // Offer an explicit recovery path only for a search/filter miss.
            // WordPress validates the shop URL against the store's allowed hosts;
            // no user-provided redirect or query parameter is ever accepted.
            if ($search !== '' || !empty($state['selected_count'])) {
                $shopUrl = function_exists('wc_get_page_permalink')
                    ? wc_get_page_permalink('shop')
                    : '';
                $shopUrl = is_string($shopUrl) && function_exists('wp_validate_redirect')
                    ? wp_validate_redirect($shopUrl, '')
                    : '';
                if ($shopUrl !== '' && function_exists('esc_url')) {
                    echo '<a class="uc-product-list__recovery" data-uc-catalog-recovery="1" href="' . esc_url($shopUrl) . '">'
                        . esc_html(__('Browse all products', 'bad-otter-ultimate-commerce-woocommerce'))
                        . '</a>';
                }
            }
        } else {
            foreach ($items as $item) {
                echo ProductCardRenderer::render($item, $context); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- renderer escapes its fields.
            }
        }
        // Embedded catalogues opt in with a merchant-owned, unfiltered page URL.
        // No URL is inferred from the current browser request or shopper input.
        if ($items !== array()) {
            echo self::renderPagination($result, $context); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- helper validates and escapes URLs and labels.
        }
        echo '</div>';
        do_action('ultimate_commerce_product_list_after', $result, $context);
        return (string) ob_get_clean();
    }

    /**
     * Optional, server-rendered previous/next navigation for host page themes.
     *
     * @param array<string,mixed> $result
     * @param array<string,mixed> $context
     */
    private static function renderPagination(array $result, array $context): string
    {
        $pagination = is_array($result['pagination'] ?? null)
            ? $result['pagination']
            : array();
        $page = (int) ($pagination['page'] ?? 1);
        $pages = (int) ($pagination['pages'] ?? 1);

        if ($page < 1 || $page > CatalogFilterRegistry::MAX_PAGE
            || $pages <= 1 || $pages > CatalogFilterRegistry::MAX_PAGE || $page > $pages) {
            return '';
        }

        $base = $context['pagination_base_url'] ?? null;
        if (!is_string($base) || $base === ''
            || !function_exists('wp_validate_redirect')
            || !function_exists('add_query_arg') || !function_exists('esc_url')) {
            return '';
        }

        $base = wp_validate_redirect($base, '');
        if (!is_string($base) || $base === '') {
            return '';
        }

        // A theme must supply the unfiltered destination. On WordPress plain
        // permalinks only the known page/archive locator query keys may remain;
        // reject any search, redirect, tracking or stale catalogue parameters.
        $parts = parse_url($base);
        if (!is_array($parts) || isset($parts['fragment'])) {
            return '';
        }
        $baseParams = array();
        if (isset($parts['query'])) {
            parse_str($parts['query'], $baseParams);
        }
        foreach ($baseParams as $key => $value) {
            if (!in_array($key, array('page_id', 'post_type', 'pagename'), true)
                || !is_scalar($value)) {
                return '';
            }
        }

        // The canonical query has already been normalised by Free's Storefront
        // registry. Do not accept arbitrary extension-supplied URL parameters.
        $canonical = is_array($result['canonical_query'] ?? null)
            ? $result['canonical_query']
            : array();
        $query = array();
        foreach ($canonical as $key => $value) {
            if (!is_string($key) || !is_string($value)
                || (!in_array($key, array('search', 'sort', 'min_price', 'max_price', 'availability', 'per_page'), true)
                    && preg_match('/^filter_[a-z0-9_-]{1,55}$/D', $key) !== 1)) {
                continue;
            }
            $query[$key] = rawurlencode($value);
        }

        $links = array();
        foreach (array('prev' => $page - 1, 'next' => $page + 1) as $direction => $target) {
            if ($target < 1 || $target > $pages) {
                continue;
            }
            $targetQuery = $query;
            if ($target > 1) {
                $targetQuery['page'] = (string) $target;
            }
            $url = add_query_arg($targetQuery, $base);
            $url = is_string($url) ? wp_validate_redirect($url, '') : '';
            if (!is_string($url) || $url === '') {
                continue;
            }
            $links[$direction] = esc_url($url);
        }

        if ($links === array()) {
            return '';
        }

        $domain = 'bad-otter-ultimate-commerce-woocommerce';
        $html = '<nav class="uc-product-list__pagination" data-uc-catalog-pagination="1" aria-label="'
            . esc_attr(__('Product catalogue pages', $domain)) . '">';
        if (isset($links['prev'])) {
            $html .= '<a class="uc-product-list__page-link uc-product-list__page-link--prev" rel="prev" href="'
                . $links['prev'] . '">' . esc_html(__('Previous page', $domain)) . '</a>';
        }
        $html .= '<span class="uc-product-list__page-current" aria-current="page">'
            . esc_html(sprintf(
                /* translators: 1: Current catalogue page, 2: Total catalogue pages. */
                __('Page %1$d of %2$d', $domain),
                $page,
                $pages
            )) . '</span>';
        if (isset($links['next'])) {
            $html .= '<a class="uc-product-list__page-link uc-product-list__page-link--next" rel="next" href="'
                . $links['next'] . '">' . esc_html(__('Next page', $domain)) . '</a>';
        }

        return $html . '</nav>';
    }

    /** @return array<string, mixed> */
    public static function filterState(array $query): array
    {
        return CatalogFilterRegistry::normalize($query);
    }
}
