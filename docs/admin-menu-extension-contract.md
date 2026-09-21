# Ultimate Commerce admin menu extension contract

Ultimate Commerce owns its own top-level WordPress admin area. UC admin pages do not live beneath the WooCommerce menu.

## Stable Free menu slugs

- root: `ultimate-commerce`
- diagnostics: `ultimate-commerce-diagnostics`

The root page is the Free Overview surface. Free currently exposes Overview and Diagnostics beneath the Ultimate Commerce top-level menu.

## Capabilities

Free read-only admin surfaces use `BadOtter\UltimateCommerce\Security\Capabilities::VIEW_DIAGNOSTICS` (`uc_view_diagnostics`). Extensions must use the exact UC capability appropriate to their page and must also enforce that capability inside the page callback.

Do not substitute broad capabilities such as `manage_options` or `manage_woocommerce` merely to make a submenu visible.

## Adding Pro or third-party pages

Free fires the public action:

```php
ultimate_commerce_admin_menu
```

The first argument is the root parent slug. Extensions may register supported subpages from that action:

```php
add_action('ultimate_commerce_admin_menu', static function (string $parentSlug): void {
    add_submenu_page(
        $parentSlug,
        __('Example', 'example-extension'),
        __('Example', 'example-extension'),
        'uc_manage_settings',
        'example-extension-page',
        'example_extension_render_page'
    );
});
```

Free never imports or calls Pro classes. Pro and legitimate third-party extensions consume this public action in the same way.

## Overview extension point

Free fires `ultimate_commerce_admin_overview` near the end of the Overview page and passes the current module-status array. Extensions may add small status/entry-point UI there when useful, but they should keep feature implementation and privileged mutations on their own pages.

## Ownership rules

- Free owns the top-level menu shell and stable root slug.
- Each module/extension owns its own submenu slug, page callback and capability checks.
- WooCommerce remains authoritative for commerce data and operations; moving UC navigation does not change that ownership boundary.
- Theme/store-specific navigation or copy does not belong in this contract.
