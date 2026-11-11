<?php

declare(strict_types=1);

namespace {
    define('ABSPATH', __DIR__ . '/');

    final class WP_Error {}

    $GLOBALS['uc_empty_query_result'] = array();
    $GLOBALS['uc_empty_actions'] = array();
    $GLOBALS['uc_empty_shop_url'] = 'https://merchant.example/shop/?campaign=fall';

    function wc_get_page_permalink(string $page): string
    {
        if ($page !== 'shop') {
            throw new \RuntimeException('Recovery must only target WooCommerce shop.');
        }

        return (string) $GLOBALS['uc_empty_shop_url'];
    }

    function wp_validate_redirect(string $url, string $fallback = ''): string
    {
        $parts = parse_url($url);
        if (!is_array($parts) || !in_array($parts['scheme'] ?? '', array('http', 'https'), true)
            || ($parts['host'] ?? '') !== 'merchant.example') {
            return $fallback;
        }

        return $url;
    }

    function esc_url($url): string
    {
        return htmlspecialchars((string) $url, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }


    function __(string $message, string $domain = ''): string
    {
        return $message;
    }

    function esc_html($value): string
    {
        return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }

    function do_action(string $hook, ...$args): void
    {
        $GLOBALS['uc_empty_actions'][] = $hook;
    }
}

namespace BadOtter\UltimateCommerce\Storefront {
    final class CatalogQuery
    {
        public static function query(array $args = array())
        {
            return $GLOBALS['uc_empty_query_result'];
        }
    }

    final class ProductCardRenderer
    {
        public static function render($view, array $context = array()): string
        {
            return '<article data-product-test="' . \esc_html((string) $view['id']) . '"></article>';
        }
    }
}

namespace {
    require_once __DIR__ . '/../packages/ultimate-commerce-for-woocommerce/src/Storefront/Catalog.php';

    use BadOtter\UltimateCommerce\Storefront\Catalog;

    function uc_empty_expect(bool $ok, string $message): void
    {
        if (!$ok) {
            throw new \RuntimeException($message);
        }
    }

    function uc_empty_fixture(array $items, array $state = array()): void
    {
        $GLOBALS['uc_empty_query_result'] = array('items' => $items, 'state' => $state);
        $GLOBALS['uc_empty_actions'] = array();
    }

    function uc_empty_hooks(): void
    {
        uc_empty_expect($GLOBALS['uc_empty_actions'] === array(
            'ultimate_commerce_product_list_before',
            'ultimate_commerce_product_list_after',
        ), 'Existing product list extension hook order must be preserved.');
    }

    uc_empty_fixture(array(array('id' => 123), array('id' => 234)), array('search' => 'jacket'));
    $found = Catalog::renderList();
    uc_empty_expect(str_contains($found, 'data-product-test="123"') && str_contains($found, 'data-product-test="234"'), 'Products must still render.');
    uc_empty_expect(!str_contains($found, 'data-uc-catalog-empty'), 'Found products must not show the empty message.');
    uc_empty_expect(!str_contains($found, 'data-uc-catalog-recovery'), 'Results must not show a recovery link.');

    uc_empty_hooks();

    $search = '<script>alert("bad")</script>';
    uc_empty_fixture(array(), array('search' => $search, 'selected_count' => 1));
    $noSearchResults = Catalog::renderList();
    uc_empty_expect(str_contains($noSearchResults, 'data-uc-catalog-empty="1" role="status"'), 'Empty search must expose an accessible status message.');
    uc_empty_expect(str_contains($noSearchResults, 'No products found for'), 'Empty search must explain which query failed.');
    uc_empty_expect(str_contains($noSearchResults, '&lt;script&gt;alert(&quot;bad&quot;)&lt;/script&gt;'), 'Reflected search must be escaped.');
    uc_empty_expect(!str_contains($noSearchResults, '<script>'), 'No user-supplied HTML may reach the output.');
    uc_empty_expect(str_contains($noSearchResults, 'data-uc-catalog-recovery="1"'), 'Zero-result searches need a usable browse-all action.');
    uc_empty_expect(str_contains($noSearchResults, 'href="https://merchant.example/shop/?campaign=fall"'), 'Recovery must use the merchant-owned Woo shop permalink without echoing search terms.');
    uc_empty_expect(str_contains($noSearchResults, 'Browse all products'), 'Recovery link needs a meaningful accessible name.');

    uc_empty_hooks();

    uc_empty_fixture(array(), array('search' => '', 'selected_count' => 2));
    $filtered = Catalog::renderList();
    uc_empty_expect(str_contains($filtered, 'No products match these filters.'), 'Empty filter results must have an actionable explanation.');
    uc_empty_expect(!str_contains($filtered, 'No products found for'), 'Filter-only state must not pretend a search was made.');
    uc_empty_expect(str_contains($filtered, 'data-uc-catalog-recovery="1"'), 'Filter-only misses need the browse-all action.');

    uc_empty_hooks();

    uc_empty_fixture(array(), array('selected_count' => 0));
    $empty = Catalog::renderList();
    uc_empty_expect(str_contains($empty, 'No products are available to display.'), 'Unfiltered empty catalog must be described.');
    uc_empty_expect(!str_contains($empty, 'data-uc-catalog-recovery'), 'Truly empty shops must not offer a misleading browse-all action.');

    uc_empty_hooks();


    // Off-site redirects, script URLs and missing shop permalinks are never
    // emitted as shopper links, even when a filter/search has no results.
    foreach (array('https://attacker.example/phishing', 'javascript:alert(1)', '') as $unsafe) {
        $GLOBALS['uc_empty_shop_url'] = $unsafe;
        uc_empty_fixture(array(), array('search' => 'blue jumper'));
        $unsafeResult = Catalog::renderList();
        uc_empty_expect(!str_contains($unsafeResult, 'data-uc-catalog-recovery'), 'Unsafe shop URL must fail closed.');
        uc_empty_hooks();
    }
    $GLOBALS['uc_empty_shop_url'] = 'https://merchant.example/shop/?utm=brand&origin=menu';
    uc_empty_fixture(array(), array('search' => 'blue jumper'));
    $encodedUrl = Catalog::renderList();
    uc_empty_expect(str_contains($encodedUrl, 'utm=brand&amp;origin=menu'), 'Recovery link attribute must be escaped.');
    uc_empty_hooks();

    $GLOBALS['uc_empty_query_result'] = new WP_Error();
    $GLOBALS['uc_empty_actions'] = array();
    uc_empty_expect(Catalog::renderList() === '', 'Existing Woo Store API error behaviour must stay unchanged.');
    uc_empty_expect($GLOBALS['uc_empty_actions'] === array(), 'Errors must not spuriously fire storefront hooks.');

    echo "PASS catalogue search/filter empty-state accessibility, escaping and list lifecycle\n";
}
