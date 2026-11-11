<?php

declare(strict_types=1);

namespace {
    define('ABSPATH', __DIR__ . '/');

    final class WP_Error {}

    $GLOBALS['uc_page_result'] = array();
    $GLOBALS['uc_page_actions'] = array();
    $GLOBALS['uc_page_render_count'] = 0;
    $GLOBALS['uc_page_queries'] = 0;
    $GLOBALS['uc_sort_pro_active'] = false;

    function __(string $text, string $domain = ''): string { return $text; }
    function esc_html($text): string { return htmlspecialchars((string) $text, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); }
    function esc_attr($text): string { return esc_html($text); }
    function esc_url($text): string { return esc_html($text); }
    function sanitize_key($text): string { return strtolower((string) preg_replace('/[^a-z0-9_-]/i', '', (string) $text)); }
    function sanitize_title($text): string { return sanitize_key($text); }
    function sanitize_text_field($text): string { return trim(strip_tags((string) $text)); }
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
        public const MAX_FILTERS = 16;
        public const MAX_OPTIONS_PER_FILTER = 100;
        public const MAX_SEARCH_LENGTH = 120;

        public static function definitions(): array
        {
            return array(
                'price' => array('type' => 'price', 'label' => 'Price'),
                'availability' => array('type' => 'availability', 'label' => 'Availability'),
                'colour' => array('type' => 'taxonomy', 'label' => 'Colour', 'param' => 'filter_colour'),
            );
        }

        public static function normaliseSearchTerm($value): string
        {
            return substr(\sanitize_text_field($value), 0, self::MAX_SEARCH_LENGTH);
        }

        public static function sortDefinitions(): array
        {
            $sorts = array(
                'newest' => array('label' => 'Newest'),
                'price_asc' => array('label' => 'Price: low to high'),
                'price_desc' => array('label' => 'Price: high to low'),
            );
            if (!empty($GLOBALS['uc_sort_pro_active'])) {
                $sorts['recommended'] = array(
                    'label' => (string) ($GLOBALS['uc_sort_pro_label'] ?? 'Recommended'),
                );
            }
            return $sorts;
        }
    }

    final class CatalogQuery
    {
        public static function query(array $args = array())
        {
            ++$GLOBALS['uc_page_queries'];
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
            'state' => array('sort' => 'newest'),
            'filters' => array(),
        );
        $GLOBALS['uc_page_actions'] = array();
        $GLOBALS['uc_page_render_count'] = 0;
        $GLOBALS['uc_page_queries'] = 0;
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

    // Sorting is opt-in. It must use the same trusted permalink contract as
    // pagination and must preserve only Free's canonical filter/search fields.
    uc_page_fixture(3, 5, array(
        'search' => 'red & navy',
        'filter_colour' => 'navy,red',
        'min_price' => '12.50',
        'availability' => 'in_stock',
        'per_page' => '24',
        'sort' => 'price_desc',
        'page' => '3',
        'redirect_to' => 'https://attacker.example/private',
    ));
    $noSort = Catalog::renderList(array(), array('pagination_base_url' => 'https://merchant.example/shop'));
    uc_page_expect(!str_contains($noSort, 'data-uc-catalog-sort'),
        'Sort form must remain opt-in even when a safe pagination permalink exists.');

    $GLOBALS['uc_page_result']['state']['sort'] = 'price_desc';
    $GLOBALS['uc_page_queries'] = 0;
    $GLOBALS['uc_page_render_count'] = 0;
    $GLOBALS['uc_page_actions'] = array();
    $sorted = Catalog::renderList(array(), array(
        'pagination_base_url' => 'https://merchant.example/?page_id=12',
        'show_sort_control' => true,
    ));
    $form = explode('</form>', $sorted, 2)[0];
    uc_page_expect(str_contains($form, 'data-uc-catalog-sort="1"') && str_contains($form, 'method="get"'),
        'Sort controls should use a native GET form without JavaScript.');
    uc_page_expect(str_contains($form, '<label') && str_contains($form, 'Sort products')
        && str_contains($form, '<select') && str_contains($form, 'name="sort"')
        && str_contains($form, 'Apply sort'), 'Native sorting requires an accessible label and explicit submit button.');
    uc_page_expect(str_contains($form, '<option value="price_desc" selected>'),
        'The validated active Free sort must be selected.');
    uc_page_expect(!str_contains($form, '<option value="recommended"'),
        'Free must not expose paid Recommended sort when Pro has not registered it.');
    uc_page_expect(str_contains($form, 'name="page_id" value="12"'),
        'Plain-permalink page locators must survive GET form submission.');
    uc_page_expect(str_contains($form, 'name="search" value="red &amp; navy"')
        && str_contains($form, 'name="filter_colour" value="navy,red"')
        && str_contains($form, 'name="min_price" value="12.50"')
        && str_contains($form, 'name="availability" value="in_stock"'),
        'Search, attribute, price and stock filters should survive a sort change.');
    uc_page_expect(!str_contains($form, 'name="page"') && !str_contains($form, 'redirect_to')
        && !str_contains($form, 'attacker.example'),
        'Changing sort must reset pagination and omit non-canonical extension query keys.');
    uc_page_expect($GLOBALS['uc_page_queries'] === 1 && $GLOBALS['uc_page_render_count'] === 1,
        'Sort form must use the current Woo result without another product query.');
    uc_page_expect($GLOBALS['uc_page_actions'] === array('ultimate_commerce_product_list_before', 'ultimate_commerce_product_list_after'),
        'Sort controls must preserve the public catalogue extension hooks.');

    $GLOBALS['uc_sort_pro_active'] = true;
    $GLOBALS['uc_sort_pro_label'] = 'Recommended <script>alert(1)</script>';
    $GLOBALS['uc_page_result']['state']['sort'] = 'recommended';
    $withPro = Catalog::renderList(array(), array(
        'pagination_base_url' => 'https://merchant.example/shop',
        'show_sort_control' => true,
    ));
    $proForm = explode('</form>', $withPro, 2)[0];
    uc_page_expect(str_contains($proForm, '<option value="recommended" selected>'),
        'Registered Pro Recommended sort must be selected when active.');
    uc_page_expect(str_contains($proForm, 'Recommended &lt;script&gt;alert(1)&lt;/script&gt;')
        && !str_contains($proForm, '<script>'),
        'Even extension-provided sort labels must be HTML escaped.');
    $GLOBALS['uc_sort_pro_active'] = false;
    unset($GLOBALS['uc_sort_pro_label']);

    foreach (array(
        'https://attacker.example/shop',
        'https://merchant.example/shop?search=stale',
        'https://merchant.example/shop#fragment',
        'https://merchant.example/?page_id%5B%5D=12',
    ) as $badSortAction) {
        uc_page_fixture(1, 3);
        $badSort = Catalog::renderList(array(), array(
            'show_sort_control' => true,
            'pagination_base_url' => $badSortAction,
        ));
        uc_page_expect(!str_contains($badSort, 'data-uc-catalog-sort'),
            'Untrusted or filtered sort destinations must fail closed: ' . $badSortAction);
    }

    uc_page_fixture(1, 3, array(), array());
    $emptySort = Catalog::renderList(array(), array(
        'pagination_base_url' => 'https://merchant.example/shop',
        'show_sort_control' => true,
    ));
    uc_page_expect(!str_contains($emptySort, 'data-uc-catalog-sort'),
        'An empty result must not render a misleading sort control.');

    // An explicit merchant opt-in turns the *same* Woo result into a native
    // search/filter GET form; only validated Free facets can become inputs.
    uc_page_fixture(3, 5, array(
        'search' => 'navy & red',
        'filter_colour' => 'navy,red',
        'availability' => 'in_stock',
        'min_price' => '12.50',
        'max_price' => '199.00',
        'sort' => 'price_desc',
        'per_page' => '12',
        'page' => '3',
        'redirect_to' => 'https://attacker.example/steal',
    ));
    $GLOBALS['uc_page_result']['state'] = array(
        'sort' => 'price_desc',
        'search' => 'navy & red',
        'filters' => array('colour' => array('navy')),
        'availability' => array('in_stock'),
        'price' => array('min' => '12.50', 'max' => '199.00'),
    );
    $GLOBALS['uc_page_result']['filters'] = array(
        array('id' => 'price', 'type' => 'price', 'label' => 'Price'),
        array(
            'id' => 'availability',
            'type' => 'availability',
            'label' => 'Availability',
            'options' => array(
                array('value' => 'in_stock', 'label' => 'In stock'),
                array('value' => 'out_of_stock', 'label' => 'Out of stock'),
                array('value' => 'on_backorder', 'label' => 'On backorder'),
            ),
        ),
        array(
            'id' => 'colour',
            'type' => 'taxonomy',
            'label' => 'Colour <script>bad()</script>',
            'options' => array(
                array('value' => 'navy', 'label' => 'Navy <script>unsafe()</script>', 'selected' => false),
                array('value' => 'red', 'label' => 'Red', 'selected' => true),
                array('value' => array('invalid'), 'label' => 'Do not emit'),
            ),
        ),
        array('id' => 'admin_override', 'type' => 'taxonomy', 'options' => array(
            array('value' => 'token', 'label' => 'Escalate privileges'),
        )),
    );

    $withoutFilters = Catalog::renderList(array(), array(
        'pagination_base_url' => 'https://merchant.example/shop',
    ));
    uc_page_expect(!str_contains($withoutFilters, 'data-uc-catalog-filters'),
        'The native search and filter form must remain off by default.');

    $GLOBALS['uc_page_queries'] = 0;
    $GLOBALS['uc_page_render_count'] = 0;
    $GLOBALS['uc_page_actions'] = array();
    $filteredMarkup = Catalog::renderList(array(), array(
        'pagination_base_url' => 'https://merchant.example/?page_id=12',
        'show_filter_controls' => true,
        'show_sort_control' => true,
    ));
    $filterForm = explode('</form>', $filteredMarkup, 2)[0];
    uc_page_expect(str_contains($filterForm, 'data-uc-catalog-filters="1"')
        && str_contains($filterForm, 'method="get"')
        && str_contains($filterForm, 'name="page_id" value="12"'),
        'The native search/facet form must use a trusted page URL and carry the WordPress page locator.');
    uc_page_expect(str_contains($filterForm, 'Search products')
        && str_contains($filterForm, 'type="search"')
        && str_contains($filterForm, 'name="search" maxlength="120" value="navy &amp; red"'),
        'The search field must have a translatable label, bound and escaped current value.');
    uc_page_expect(str_contains($filterForm, 'type="hidden" name="sort" value="price_desc"')
        && str_contains($filterForm, 'type="hidden" name="per_page" value="12"'),
        'Search and filter changes must preserve the validated sort and page size.');
    uc_page_expect(str_contains($filterForm, 'name="filter_colour[]" value="navy" checked')
        && str_contains($filterForm, 'name="filter_colour[]" value="red"')
        && !str_contains($filterForm, 'name="filter_colour[]" value="red" checked'),
        'Selected facet controls must reflect validated Free state, not extension-supplied selected flags.');
    uc_page_expect(str_contains($filterForm, 'name="availability[]" value="in_stock" checked')
        && str_contains($filterForm, 'name="availability[]" value="out_of_stock"'),
        'Availability controls must include only registered Woo stock states and validated selections.');
    uc_page_expect(str_contains($filterForm, 'name="min_price" value="12.50"')
        && str_contains($filterForm, 'name="max_price" value="199.00"')
        && str_contains($filterForm, '<fieldset') && str_contains($filterForm, '<legend>'),
        'Minimum and maximum price controls and facets must be semantic and preserve validated values.');
    uc_page_expect(str_contains($filterForm, 'Colour &lt;script&gt;bad()&lt;/script&gt;')
        && str_contains($filterForm, 'Navy &lt;script&gt;unsafe()&lt;/script&gt;')
        && !str_contains($filterForm, '<script>'),
        'Extension labels must be escaped before reaching HTML or fieldset legends.');
    uc_page_expect(str_contains($filterForm, 'Apply filters')
        && !str_contains($filterForm, 'name="page"')
        && !str_contains($filterForm, 'name="admin_override"')
        && !str_contains($filterForm, 'redirect_to')
        && !str_contains($filterForm, 'attacker.example'),
        'Submitting facets must reset page and exclude unregistered fields or redirect values.');
    uc_page_expect(str_contains($filteredMarkup, 'data-uc-catalog-sort="1"')
        && str_contains($filteredMarkup, 'data-uc-catalog-pagination="1"')
        && str_contains($filteredMarkup, 'data-id="17"'),
        'Search/filter, sort, existing Woo product cards and paging must coexist.');
    uc_page_expect($GLOBALS['uc_page_queries'] === 1 && $GLOBALS['uc_page_render_count'] === 1
        && $GLOBALS['uc_page_actions'] === array('ultimate_commerce_product_list_before', 'ultimate_commerce_product_list_after'),
        'Combined controls must query Woo once and preserve the existing public render hooks.');

    // A zero-result search still exposes filter recovery without another Woo
    // query; errors stay distinct from genuine no-result pages.
    $GLOBALS['uc_page_result']['items'] = array();
    $GLOBALS['uc_page_result']['state']['search'] = 'nothing';
    $GLOBALS['uc_page_result']['canonical_query']['search'] = 'nothing';
    $emptyFiltered = Catalog::renderList(array(), array(
        'pagination_base_url' => 'https://merchant.example/shop',
        'show_filter_controls' => true,
        'show_sort_control' => true,
    ));
    uc_page_expect(str_contains($emptyFiltered, 'data-uc-catalog-filters="1"')
        && str_contains($emptyFiltered, 'data-uc-catalog-empty="1"')
        && !str_contains($emptyFiltered, 'data-uc-catalog-sort="1"')
        && !str_contains($emptyFiltered, 'data-uc-catalog-pagination="1"'),
        'Zero-result shoppers should be able to adjust filters without misleading sort/page controls.');

    foreach (array(
        'https://attacker.example/shop',
        'https://merchant.example/shop#ref',
        'https://merchant.example/shop?search=stale',
        'https://merchant.example/?page_id%5B%5D=12',
    ) as $unsafeBase) {
        $output = Catalog::renderList(array(), array(
            'pagination_base_url' => $unsafeBase,
            'show_filter_controls' => true,
        ));
        uc_page_expect(!str_contains($output, 'data-uc-catalog-filters'),
            'Untrusted search/filter form destinations must fail closed.');
    }

    // Even a malformed extension descriptor cannot generate >100 facet
    // choices or mutate a registered facet into a new filter field.
    uc_page_fixture(1, 2);
    $manyOptions = array();
    for ($i = 0; $i < 150; ++$i) {
        $manyOptions[] = array('value' => 'shade' . $i, 'label' => 'Shade ' . $i);
    }
    $GLOBALS['uc_page_result']['filters'] = array(
        array('id' => 'colour', 'type' => 'taxonomy', 'label' => 'Colour', 'options' => $manyOptions),
    );
    $largeFacet = Catalog::renderList(array(), array(
        'pagination_base_url' => 'https://merchant.example/shop',
        'show_filter_controls' => true,
    ));
    uc_page_expect(substr_count($largeFacet, 'name="filter_colour[]"') === 100
        && !str_contains($largeFacet, 'value="shade100"'),
        'Extension-provided descriptor options must be bounded to 100 per facet.');

    $GLOBALS['uc_page_result'] = new WP_Error();
    $GLOBALS['uc_page_actions'] = array();
    uc_page_expect(Catalog::renderList(array(), array('pagination_base_url' => 'https://merchant.example/shop')) === '',
        'Store API failures must retain the existing no-markup error behaviour.');
    uc_page_expect($GLOBALS['uc_page_actions'] === array(), 'Store API errors must not call catalogue hooks.');

    fwrite(STDOUT, "PASS opt-in search/filter/sort/pagination native integration, trusted URLs, bounded facets and lifecycle hooks\n");
}
