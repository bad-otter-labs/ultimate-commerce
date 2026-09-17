<?php

declare(strict_types=1);

define('ABSPATH', __DIR__ . '/');

$ucTestActions = array();
$ucTestMenus = array();
$ucTestSubmenus = array();
$ucTestFiredActions = array();

function __(string $text, string $domain = ''): string
{
    return $text;
}

function add_action(string $hook, $callback, int $priority = 10, int $acceptedArgs = 1): void
{
    global $ucTestActions;
    $ucTestActions[] = array($hook, $callback, $priority, $acceptedArgs);
}

function add_menu_page($pageTitle, $menuTitle, $capability, $menuSlug, $callback = '', $iconUrl = '', $position = null): string
{
    global $ucTestMenus;
    $ucTestMenus[] = compact('pageTitle', 'menuTitle', 'capability', 'menuSlug', 'callback', 'iconUrl', 'position');
    return 'toplevel_page_' . $menuSlug;
}

function add_submenu_page($parentSlug, $pageTitle, $menuTitle, $capability, $menuSlug, $callback = '', $position = null): string
{
    global $ucTestSubmenus;
    $ucTestSubmenus[] = compact('parentSlug', 'pageTitle', 'menuTitle', 'capability', 'menuSlug', 'callback', 'position');
    return $parentSlug . '_page_' . $menuSlug;
}

function do_action(string $hook, ...$args): void
{
    global $ucTestFiredActions;
    $ucTestFiredActions[] = array($hook, $args);
}

function ucAssert(bool $condition, string $message): void
{
    if (!$condition) {
        fwrite(STDERR, "FAIL: {$message}\n");
        exit(1);
    }
}

require_once __DIR__ . '/../packages/ultimate-commerce-for-woocommerce/src/Security/Capabilities.php';
require_once __DIR__ . '/../packages/ultimate-commerce-for-woocommerce/src/Admin/AdminMenu.php';

use BadOtter\UltimateCommerce\Admin\AdminMenu;
use BadOtter\UltimateCommerce\Security\Capabilities;

AdminMenu::hooks();

ucAssert(count($ucTestActions) === 1, 'Admin menu must register one admin_menu hook.');
ucAssert($ucTestActions[0][0] === 'admin_menu', 'Admin menu must hook admin_menu.');
ucAssert($ucTestActions[0][2] === 20, 'Admin menu priority must remain deterministic.');

AdminMenu::register();

ucAssert(count($ucTestMenus) === 1, 'Ultimate Commerce must register one top-level menu.');
$root = $ucTestMenus[0];
ucAssert($root['menuSlug'] === AdminMenu::ROOT_SLUG, 'Top-level slug must be ultimate-commerce.');
ucAssert($root['menuTitle'] === 'Ultimate Commerce', 'Top-level menu title must be Ultimate Commerce.');
ucAssert($root['capability'] === Capabilities::VIEW_DIAGNOSTICS, 'Top-level menu must use the UC diagnostics capability.');
ucAssert($root['iconUrl'] === 'dashicons-store', 'Top-level menu must use the store icon.');

ucAssert(count($ucTestSubmenus) === 2, 'Free must register Overview and Diagnostics submenus.');
foreach ($ucTestSubmenus as $submenu) {
    ucAssert($submenu['parentSlug'] === AdminMenu::ROOT_SLUG, 'All Free admin pages must live under the UC root menu.');
    ucAssert($submenu['parentSlug'] !== 'woocommerce', 'UC admin pages must not live under WooCommerce.');
    ucAssert($submenu['capability'] === Capabilities::VIEW_DIAGNOSTICS, 'Free read-only admin pages must use the UC diagnostics capability.');
}

ucAssert($ucTestSubmenus[0]['menuSlug'] === AdminMenu::ROOT_SLUG, 'Overview must be the root landing page.');
ucAssert($ucTestSubmenus[1]['menuSlug'] === AdminMenu::DIAGNOSTICS_SLUG, 'Diagnostics must use its stable UC submenu slug.');

ucAssert(count($ucTestFiredActions) === 1, 'Admin extension action must fire once.');
ucAssert($ucTestFiredActions[0][0] === 'uc_admin_menu', 'Public admin extension action is missing.');
ucAssert($ucTestFiredActions[0][1] === array(AdminMenu::ROOT_SLUG), 'Admin extension action must expose the UC parent slug.');

echo "Ultimate Commerce admin menu contract passed.\n";
