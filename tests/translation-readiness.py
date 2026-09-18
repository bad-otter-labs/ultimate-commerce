#!/usr/bin/env python3
from pathlib import Path
import re

ROOT = Path(__file__).resolve().parents[1]
PACKAGE = ROOT / 'packages' / 'ultimate-commerce-for-woocommerce'
if not PACKAGE.exists():
    PACKAGE = ROOT
DOMAIN = 'ultimate-commerce-for-woocommerce'

plugin = (PACKAGE / 'ultimate-commerce-for-woocommerce.php').read_text()
if 'Text Domain: ' + DOMAIN not in plugin:
    raise SystemExit('Canonical plugin text domain is missing or incorrect.')
if 'Domain Path: /languages' not in plugin:
    raise SystemExit('Canonical plugin Domain Path must be /languages.')

php_files = [PACKAGE / 'ultimate-commerce-for-woocommerce.php'] + sorted((PACKAGE / 'src').rglob('*.php'))
dynamic = re.compile(r"(?:__|_e|esc_html__|esc_attr__|_x|esc_html_x|esc_attr_x|_n|_nx)\(\s*\$")
for path in php_files:
    text = path.read_text()
    if dynamic.search(text):
        raise SystemExit(f'Dynamic translation string is not extractable: {path.relative_to(PACKAGE)}')
    if re.search(r"self::error\([^,\n]+,\s*'", text):
        raise SystemExit(f'Error helper still receives an untranslated literal: {path.relative_to(PACKAGE)}')

cart_php = (PACKAGE / 'src/Storefront/CartDrawer.php').read_text()
variation_php = (PACKAGE / 'src/Storefront/ProductCardRenderer.php').read_text()
wishlist_php = (PACKAGE / 'src/Storefront/WishlistControls.php').read_text()
for label, text, handle in (
    ('cart drawer', cart_php, 'self::SCRIPT_HANDLE'),
    ('variation controls', variation_php, 'self::VARIATION_SCRIPT_HANDLE'),
    ('wishlist', wishlist_php, 'self::SCRIPT_HANDLE'),
):
    if "array('wp-i18n')" not in text:
        raise SystemExit(f'{label} script must depend on wp-i18n.')
    marker = f"wp_set_script_translations({handle}, '{DOMAIN}', ULTIMATE_COMMERCE_DIR . 'languages');"
    if marker not in text:
        raise SystemExit(f'{label} script translations are not registered.')

cart_js = (PACKAGE / 'assets/js/cart-drawer.js').read_text()
variation_js = (PACKAGE / 'assets/js/variation-controls.js').read_text()
wishlist_js = (PACKAGE / 'assets/js/wishlist.js').read_text()
if 'wp.i18n.__' not in cart_js or 'wp.i18n._n' not in cart_js or 'wp.i18n.sprintf' not in cart_js:
    raise SystemExit('Cart drawer must use WordPress JS i18n helpers.')
if 'wp.i18n.__' not in variation_js:
    raise SystemExit('Variation controller must use WordPress JS i18n helpers for fallback labels.')
if 'wp.i18n.__' not in wishlist_js or 'wp.i18n._n' not in wishlist_js or 'wp.i18n.sprintf' not in wishlist_js:
    raise SystemExit('Wishlist controller must use WordPress JS i18n helpers.')

cart_messages = (
    'Cart request failed.',
    'Cart request failed (%d).',
    'Loading cart…',
    'Updating cart…',
    'Cart updated.',
    'Decrease quantity',
    'Quantity for %s',
    'cart item',
    'Increase quantity',
    'Remove',
    'The cart could not be updated.',
    'Adding to cart…',
)
for message in cart_messages:
    marker = f"__('{message}', '{DOMAIN}')"
    if marker not in cart_js:
        raise SystemExit(f'Cart drawer UI string is not routed through wp-i18n: {message}')
if f"_n('%d item in cart', '%d items in cart', count, '{DOMAIN}')" not in cart_js:
    raise SystemExit('Cart item-count accessibility label must use plural-aware wp-i18n.')

for message in ('Add to cart', 'Unavailable', 'Select options'):
    marker = f"__( '{message}', '{DOMAIN}' )"
    if marker not in variation_js:
        raise SystemExit(f'Variation fallback label is not routed through wp-i18n: {message}')

integration = ROOT / 'docs/theme-store-integration.md'
translation_doc = ROOT / 'docs/translation-readiness.md'
if not integration.is_file() or not translation_doc.is_file():
    raise SystemExit('Theme/store integration and translation-readiness documentation must ship together.')

integration_text = integration.read_text()
for marker in (
    'ULTIMATE_COMMERCE_STOREFRONT_API_VERSION',
    'ULTIMATE_COMMERCE_CART_API_VERSION',
    'uc_cart_drawer_auto_render',
    'uc:variation-change',
    'WooCommerce remains authoritative',
):
    if marker not in integration_text:
        raise SystemExit(f'Integration guide is missing supported contract marker: {marker}')

print('Ultimate Commerce translation/integration readiness validated.')
