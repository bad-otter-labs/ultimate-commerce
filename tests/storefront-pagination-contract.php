<?php

declare(strict_types=1);

namespace {
    define('ABSPATH', __DIR__ . '/');

    final class WP_Error {}

    $GLOBALS['uc_page_result'] = array();
    $GLOBALS['uc_page_actions'] = array();
    $GLOBALS['uc_page_render_count'] = 0;

    function __(string $text, string $domain = ''): string { return $text; }
    function esc_html($text): string { return htmlspecialchars((string) $text, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); }
    function esc_attr($text): string { return esc_html($text); }
    function esc_url($text): string { return esc_html($text); }
    function do_action(string $hook, ...$args): void { $GLOBALS['uc_page_actions'][] = $hook; }

    function wp_parse_url(string $url)
    {
        return parse_url($url);
    }

    function wp_validate_redirect(string $url, string $fallback = ''): string
    {
        $parts = parse_url($url);
        if (!is_array($parts) || ($parts['host'] ?? '') !== 'merchant.example'
            || !in_array($parts['scheme'] ?? '', array('http', 'https'), true)) {
            return $fallback;
        }

        return $url;
    }

    function add_query_arg(array $args, string $url): string
    {
        // Mirror the WordPress URL/query assembly contract: target fields are
        // already percent-encoded by Catalog, and original page locators remain.
        $parts = parse_url($url);
        $params = array();
        if (is_array($parts) && isset($parts['query'])) {
            parse_str($parts['query'], $params);
        }
        foreach ($args as $key => $value) {
            $params[$key] = $value;
        }

        $base = explode('?', $url, 2)[0];
        $pairs = array();
        foreach ($params as $key => $value) {
            $pairs[] = $key . '=' . (string) $value;
        }

        return $base . ($pairs === array() ? '' : '?' . implode('&', $pairs));
    }
}

namespace BadOtter\UltimateCommerce\Storefront {
    final class CatalogFilterRegistry
    {
        public const MAX_PAGE = 500;
    }

    final class CatalogQuery
    {
        public static function query(array $args = array())
        {
            return $GLOBALS['uc_page_result'];
        }
    }

    final class ProductCardRenderer
    {
        public static function render($item, array $context = array()): string
        {
            ++$GLOBALS['uc_page_render_count'];
            return '<article data-id="' . \esc_attr((string) ($item['id'] ?? 0)) . '"></article>';
        }
    }
}

namespace {
    require_once __DIR__ . '/../packages/ultimate-commerce-for-woocommerce/src/Storefront/Catalog.php';

    use BadOtter\UltimateCommerce\Storefront\Catalog;

    function uc_page_expect(bool $condition, string $message): void
    {
        if (!$condition) {
            throw new \RuntimeException($message);
        }
    }

    function uc_page_fixture(int $page, int $pages, array $canonical = array(), array $items = array(array('id' => 17))): void
    {
        $GLOBALS['uc_page_result'] = array(
            'items' => $items,
            'pagination' => array('page' => $page, 'pages' => $pages, 'total' => 20),
            'canonical_query' => $canonical,
            'state' => array(),
        );
        $GLOBALS['uc_page_actions'] = array();
        $GLOBALS['uc_page_render_count'] = 0;
    }

    uc_page_fixture(1, 4, array('search' => 'red & navy', 'filter_colour' => 'blue,red', 'per_page' => '2'));
    $withoutBase = Catalog::renderList();
    uc_page_expect(!str_contains($withoutBase, 'data-uc-catalog-pagination'), 'Catalogues must not guess a shopper URL without a theme opt-in.');
    uc_page_expect($GLOBALS['uc_page_render_count'] === 1, 'Default catalogue cards must remain rendered.');
    uc_page_expect($GLOBALS['uc_page_actions'] === array('ultimate_commerce_product_list_before', 'ultimate_commerce_product_list_after'),
        'Catalogue extension lifecycle must remain intact.');

    uc_page_fixture(1, 4, array('search' => 'red & navy', 'filter_colour' => 'blue,red', 'per_page' => '2', 'redirect_to' => 'https://bad.example'));
    $first = Catalog::renderList(array(), array('pagination_base_url' => 'https://merchant.example/shop'));
    uc_page_expect(str_contains($first, '<nav class="uc-product-list__pagination" data-uc-catalog-pagination="1" aria-label="Product catalogue pages">'),
        'Opt-in pagination must expose a labelled accessible navigation landmark.');
    uc_page_expect(str_contains($first, 'Page 1 of 4') && str_contains($first, 'aria-current="page"'), 'A page indicator is required.');
    uc_page_expect(str_contains($first, 'rel="next"') && !str_contains($first, 'rel="prev"'), 'First page must have only Next.');
    uc_page_expect(str_contains($first, 'search=red%20%26%20navy'), 'Search text must be URL encoded rather than injected into markup.');
    uc_page_expect(str_contains($first, 'filter_colour=blue%2Cred'), 'Registered canonical filters must survive navigation.');
    uc_page_expect(str_contains($first, 'per_page=2&amp;page=2'), 'Per-page state and target page must be preserved.');
    uc_page_expect(!str_contains($first, 'redirect_to') && !str_contains($first, 'bad.example'),
        'Untrusted extension query keys must never be attached to pagination links.');

    uc_page_fixture(3, 4, array('search' => 'a <script> & b', 'sort' => 'recommended'));
    $middle = Catalog::renderList(array(), array('pagination_base_url' => 'https://merchant.example/?page_id=12'));
    uc_page_expect(str_contains($middle, 'rel="prev"') && str_contains($middle, 'rel="next"'),
        'Middle pages need both Previous and Next actions.');
    uc_page_expect(str_contains($middle, 'Page 3 of 4'), 'Middle-page position must be localised/announced.');
    uc_page_expect(str_contains($middle, 'page_id=12') && str_contains($middle, 'page=2') && str_contains($middle, 'page=4'),
        'WordPress plain permalink page ID must be retained.');
    uc_page_expect(str_contains($middle, 'search=a%20%3Cscript%3E%20%26%20b') && !str_contains($middle, '<script>'),
        'Malicious search text must never enter an HTML attribute as executable markup.');

    uc_page_fixture(4, 4);
    $last = Catalog::renderList(array(), array('pagination_base_url' => 'https://merchant.example/shop'));
    uc_page_expect(str_contains($last, 'rel="prev"') && !str_contains($last, 'rel="next"'), 'Last page must have only Previous.');
    uc_page_expect(str_contains($last, 'page=3'), 'Last page link must move exactly one page backwards.');

    foreach (array(
        '',
        'https://attacker.example/shop',
        'javascript:alert(1)',
        'https://merchant.example/shop#redirect',
        'https://merchant.example/shop?search=stale',
        'https://merchant.example/shop?redirect_to=https%3A%2F%2Fattacker.example',
        'https://merchant.example/?page_id%5B%5D=12',
    ) as $badBase) {
        uc_page_fixture(1, 3);
        $markup = Catalog::renderList(array(), array('pagination_base_url' => $badBase));
        uc_page_expect(!str_contains($markup, 'data-uc-catalog-pagination'),
            'Unsafe, stale or browser-controlled pagination base must fail closed: ' . $badBase);
    }

    uc_page_fixture(499, 800);
    $upper = Catalog::renderList(array(), array('pagination_base_url' => 'https://merchant.example/shop'));
    uc_page_expect(str_contains($upper, 'Page 499 of 500') && str_contains($upper, 'page=500'),
        'Large Woo catalogues must still page until Free public MAX_PAGE without offering page 501.');
    uc_page_fixture(500, 800);
    $limit = Catalog::renderList(array(), array('pagination_base_url' => 'https://merchant.example/shop'));
    uc_page_expect(str_contains($limit, 'rel="prev"') && !str_contains($limit, 'rel="next"'),
        'Maximum supported page must terminate Next navigation.');

    foreach (array(array(0, 3), array(501, 503), array(4, 3), array(1, 1)) as [$page, $pages]) {
        uc_page_fixture($page, $pages);
        $markup = Catalog::renderList(array(), array('pagination_base_url' => 'https://merchant.example/shop'));
        uc_page_expect(!str_contains($markup, 'data-uc-catalog-pagination'), 'Invalid or single-page result metadata must not generate links.');
    }

    uc_page_fixture(1, 3, array(), array());
    $noItems = Catalog::renderList(array(), array('pagination_base_url' => 'https://merchant.example/shop'));
    uc_page_expect(!str_contains($noItems, 'data-uc-catalog-pagination'), 'Do not present paging controls with no visible result cards.');

    $GLOBALS['uc_page_result'] = new WP_Error();
    $GLOBALS['uc_page_actions'] = array();
    uc_page_expect(Catalog::renderList(array(), array('pagination_base_url' => 'https://merchant.example/shop')) === '',
        'Store API failures must retain the existing no-markup error behaviour.');
    uc_page_expect($GLOBALS['uc_page_actions'] === array(), 'Store API errors must not call catalogue hooks.');

    fwrite(STDOUT, "PASS accessible opt-in catalogue paging, canonical search state, trusted destinations and existing hooks\n");
}
