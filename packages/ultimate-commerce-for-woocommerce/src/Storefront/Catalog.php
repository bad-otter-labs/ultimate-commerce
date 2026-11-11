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
        if ($items !== array() && !empty($context['show_sort_control'])) {
            echo self::renderSortControl($result, $context); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- helper escapes the form URL, attributes and text.
        }
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
        // Most embedded catalogues do not opt into navigation. Nothing in
        // Free derives a navigation destination from the browser request.
        $base = self::trustedCatalogueBase($context);
        if ($base === null || !function_exists('add_query_arg')) {
            return '';
        }

        $pagination = is_array($result['pagination'] ?? null)
            ? $result['pagination']
            : array();
        $page = (int) ($pagination['page'] ?? 1);
        // Woo may report more total pages than Free's public API permits.
        $pages = max(1, min(CatalogFilterRegistry::MAX_PAGE, (int) ($pagination['pages'] ?? 1)));
        if ($page < 1 || $page > CatalogFilterRegistry::MAX_PAGE
            || $pages <= 1 || $page > $pages) {
            return '';
        }

        $query = array();
        foreach (self::canonicalUrlFields($result) as $key => $value) {
            if ($key !== 'page') {
                $query[$key] = rawurlencode($value);
            }
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
            $url = add_query_arg($targetQuery, $base['url']);
            $url = is_string($url) ? wp_validate_redirect($url, '') : '';
            if (!is_string($url) || $url === '') {
                continue;
            }
            $links[$direction] = esc_url($url);
        }

        if ($links === array()) {
            return '';
        }

        $html = '<nav class="uc-product-list__pagination" data-uc-catalog-pagination="1" aria-label="'
            . esc_attr(__('Product catalogue pages', 'bad-otter-ultimate-commerce-woocommerce')) . '">';
        if (isset($links['prev'])) {
            $html .= '<a class="uc-product-list__page-link uc-product-list__page-link--prev" rel="prev" href="'
                . $links['prev'] . '">' . esc_html(__('Previous page', 'bad-otter-ultimate-commerce-woocommerce')) . '</a>';
        }
        $html .= '<span class="uc-product-list__page-current" aria-current="page">'
            . esc_html(sprintf(
                /* translators: 1: Current catalogue page, 2: Total catalogue pages. */
                __('Page %1$d of %2$d', 'bad-otter-ultimate-commerce-woocommerce'),
                $page,
                $pages
            )) . '</span>';
        if (isset($links['next'])) {
            $html .= '<a class="uc-product-list__page-link uc-product-list__page-link--next" rel="next" href="'
                . $links['next'] . '">' . esc_html(__('Next page', 'bad-otter-ultimate-commerce-woocommerce')) . '</a>';
        }

        return $html . '</nav>';
    }

    /**
     * Optional native GET form for the already-registered Free/Pro catalogue sorts.
     * The form preserves validated search/filter state but deliberately drops
     * the old page number so the new sort starts from page one.
     *
     * @param array<string, mixed> $result
     * @param array<string, mixed> $context
     */
    private static function renderSortControl(array $result, array $context): string
    {
        $base = self::trustedCatalogueBase($context);
        if ($base === null) {
            return '';
        }

        $definitions = CatalogFilterRegistry::sortDefinitions();
        if (count($definitions) < 2) {
            return '';
        }

        $state = is_array($result['state'] ?? null) ? $result['state'] : array();
        $current = (string) ($state['sort'] ?? 'newest');
        if (!isset($definitions[$current])) {
            $current = 'newest';
        }

        $html = '<form class="uc-product-list__sort-form" data-uc-catalog-sort="1" method="get" action="'
            . esc_url($base['url']) . '">';

        // GET form submission replaces any query on the form action. Keep
        // WordPress's vetted plain-permalink/page locator keys explicitly.
        foreach ($base['locators'] as $key => $value) {
            $html .= '<input type="hidden" name="' . esc_attr($key) . '" value="' . esc_attr($value) . '">';
        }

        foreach (self::canonicalUrlFields($result) as $key => $value) {
            if ($key !== 'sort' && $key !== 'page') {
                $html .= '<input type="hidden" name="' . esc_attr($key) . '" value="' . esc_attr($value) . '">';
            }
        }

        $html .= '<label class="uc-product-list__sort-label">'
            . esc_html(__('Sort products', 'bad-otter-ultimate-commerce-woocommerce'))
            . ' <select class="uc-product-list__sort-select" name="sort">';
        foreach ($definitions as $key => $definition) {
            $html .= '<option value="' . esc_attr((string) $key) . '"'
                . ($current === (string) $key ? ' selected' : '') . '>'
                . esc_html((string) ($definition['label'] ?? $key)) . '</option>';
        }
        $html .= '</select></label>'
            . '<button type="submit" class="uc-product-list__sort-submit">'
            . esc_html(__('Apply sort', 'bad-otter-ultimate-commerce-woocommerce'))
            . '</button></form>';

        return $html;
    }

    /**
     * Only an explicit theme-supplied, unfiltered, same-site permalink may
     * become a GET action or a previous/next navigation target.
     *
     * @param array<string, mixed> $context
     * @return array{url: string, locators: array<string, string>}|null
     */
    private static function trustedCatalogueBase(array $context): ?array
    {
        $base = $context['pagination_base_url'] ?? null;
        if (!is_string($base) || $base === '' || !function_exists('wp_validate_redirect')
            || !function_exists('wp_parse_url') || !function_exists('esc_url')) {
            return null;
        }
        $base = wp_validate_redirect($base, '');
        if (!is_string($base) || $base === '') {
            return null;
        }

        $parts = wp_parse_url($base);
        if (!is_array($parts) || isset($parts['fragment'])) {
            return null;
        }
        $baseParams = array();
        if (isset($parts['query'])) {
            parse_str($parts['query'], $baseParams);
        }
        foreach ($baseParams as $key => $value) {
            if (!in_array($key, array('page_id', 'post_type', 'pagename'), true)
                || !is_string($value)) {
                return null;
            }
        }

        return array('url' => $base, 'locators' => $baseParams);
    }

    /**
     * Free owns the normalised catalogue search and filter contract. Never
     * echo arbitrary extension-provided keys into form fields or page links.
     *
     * @param array<string, mixed> $result
     * @return array<string, string>
     */
    private static function canonicalUrlFields(array $result): array
    {
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
            $query[$key] = $value;
        }

        return $query;
    }

    /** @return array<string, mixed> */
    public static function filterState(array $query): array
    {
        return CatalogFilterRegistry::normalize($query);
    }
}
