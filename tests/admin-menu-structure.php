<?php

declare(strict_types=1);

define('ABSPATH', __DIR__ . '/');

$ucTestActions = array();
$ucTestMenus = array();
$ucTestSubmenus = array();
$ucTestFiredActions = array();

$ucOverviewSource = file_get_contents(__DIR__ . '/../packages/ultimate-commerce-for-woocommerce/src/Admin/OverviewPage.php');
if (!is_string($ucOverviewSource)) {
    throw new RuntimeException('Unable to read Ultimate Commerce Overview source.');
}

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
require_once __DIR__ . '/../packages/ultimate-commerce-for-woocommerce/src/Admin/ModulesPage.php';
require_once __DIR__ . '/../packages/ultimate-commerce-for-woocommerce/src/Admin/SettingsPage.php';
require_once __DIR__ . '/../packages/ultimate-commerce-for-woocommerce/src/Admin/AdminMenu.php';

use BadOtter\UltimateCommerce\Admin\AdminMenu;
use BadOtter\UltimateCommerce\Admin\ModulesPage;
use BadOtter\UltimateCommerce\Admin\SettingsPage;
use BadOtter\UltimateCommerce\Security\Capabilities;

AdminMenu::hooks();

ucAssert(count($ucTestActions) === 2, 'Admin menu must register navigation and scoped asset hooks.');
ucAssert($ucTestActions[0][0] === 'admin_menu', 'Admin menu must hook admin_menu.');
ucAssert($ucTestActions[0][2] === 20, 'Admin menu priority must remain deterministic.');
ucAssert($ucTestActions[1][0] === 'admin_enqueue_scripts', 'Admin UI assets must use the WordPress admin enqueue hook.');
ucAssert($ucTestActions[1][1] === array(AdminMenu::class, 'enqueueAssets'), 'Admin UI asset loading must remain owned by AdminMenu.');

AdminMenu::register();

ucAssert(count($ucTestMenus) === 1, 'Ultimate Commerce must register one top-level menu.');
$root = $ucTestMenus[0];
ucAssert($root['menuSlug'] === AdminMenu::ROOT_SLUG, 'Top-level slug must be ultimate-commerce.');
ucAssert($root['menuTitle'] === 'Ultimate Commerce', 'Top-level menu title must be Ultimate Commerce.');
ucAssert($root['capability'] === Capabilities::VIEW_DIAGNOSTICS, 'Top-level menu must use the UC diagnostics capability.');
ucAssert($root['iconUrl'] === 'dashicons-store', 'Top-level menu must use the store icon.');

ucAssert(count($ucTestSubmenus) === 4, 'Free must register Overview, Modules, Settings and Diagnostics submenus.');
foreach ($ucTestSubmenus as $submenu) {
    ucAssert($submenu['parentSlug'] === AdminMenu::ROOT_SLUG, 'All Free admin pages must live under the UC root menu.');
    ucAssert($submenu['parentSlug'] !== 'woocommerce', 'UC admin pages must not live under WooCommerce.');
}

ucAssert($ucTestSubmenus[0]['menuSlug'] === AdminMenu::ROOT_SLUG, 'Overview must be the root landing page.');
ucAssert($ucTestSubmenus[0]['capability'] === Capabilities::VIEW_DIAGNOSTICS, 'Overview must remain readable through the diagnostics capability.');
ucAssert($ucTestSubmenus[1]['menuSlug'] === ModulesPage::SLUG, 'Modules must use its stable UC submenu slug.');
ucAssert($ucTestSubmenus[1]['capability'] === Capabilities::MANAGE_SETTINGS, 'Module management must require the exact UC settings capability.');
ucAssert($ucTestSubmenus[2]['menuSlug'] === SettingsPage::SLUG, 'Settings transfer must use its stable UC submenu slug.');
ucAssert($ucTestSubmenus[2]['capability'] === Capabilities::MANAGE_SETTINGS, 'Settings transfer must require the exact UC settings capability.');
ucAssert($ucTestSubmenus[3]['menuSlug'] === AdminMenu::DIAGNOSTICS_SLUG, 'Diagnostics must use its stable UC submenu slug.');
ucAssert($ucTestSubmenus[3]['capability'] === Capabilities::VIEW_DIAGNOSTICS, 'Diagnostics must remain readable through the diagnostics capability.');

ucAssert(count($ucTestFiredActions) === 1, 'Admin extension action must fire once.');
ucAssert($ucTestFiredActions[0][0] === 'ultimate_commerce_admin_menu', 'Public admin extension action is missing.');
ucAssert($ucTestFiredActions[0][1] === array(AdminMenu::ROOT_SLUG), 'Admin extension action must expose the UC parent slug.');

ucAssert(str_contains($ucOverviewSource, "esc_html__('Ultimate Commerce Pro'"), 'Overview must contain restrained Pro discovery.');
ucAssert(str_contains($ucOverviewSource, 'optional paid companion'), 'Pro discovery must explain that Pro is optional.');
ucAssert(str_contains($ucOverviewSource, 'Free remains usable without Pro'), 'Pro discovery must preserve standalone Free positioning.');
ucAssert(str_contains($ucOverviewSource, 'https://github.com/bad-otter-labs/ultimate-commerce/tree/main/docs'), 'Overview documentation link must point to public project docs.');
ucAssert(!str_contains($ucOverviewSource, "add_action('admin_notices'"), 'Pro discovery must not become a site-wide admin notice.');
ucAssert(!str_contains($ucOverviewSource, "add_action('wp_dashboard_setup'"), 'Pro discovery must not become a dashboard widget.');

echo "Ultimate Commerce admin menu contract passed.\n";
