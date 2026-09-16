<?php

declare(strict_types=1);

define('ABSPATH', __DIR__ . '/');

final class WP_Error
{
    /** @param array<string, mixed> $data */
    public function __construct(
        public string $code,
        public string $message,
        public array $data = array()
    ) {
    }
}

final class UcTestRole
{
    /** @var array<string, bool> */
    public array $capabilities = array();

    public function add_cap(string $capability): void
    {
        $this->capabilities[$capability] = true;
    }
}

$GLOBALS['uc_test_options'] = array();
$GLOBALS['uc_test_roles'] = array(
    'administrator' => new UcTestRole(),
    'shop_manager' => new UcTestRole(),
);
$GLOBALS['uc_test_caps'] = array();
$GLOBALS['uc_test_user_id'] = 0;
$GLOBALS['uc_test_routes'] = array();

function get_option($name, $default = false)
{
    return $GLOBALS['uc_test_options'][$name] ?? $default;
}

function update_option($name, $value, $autoload = null): bool
{
    $GLOBALS['uc_test_options'][$name] = $value;
    return true;
}

function get_role($name)
{
    return $GLOBALS['uc_test_roles'][$name] ?? null;
}

function current_user_can($capability): bool
{
    return !empty($GLOBALS['uc_test_caps'][$capability]);
}

function get_current_user_id(): int
{
    return (int) $GLOBALS['uc_test_user_id'];
}

function is_user_logged_in(): bool
{
    return get_current_user_id() > 0;
}

function sanitize_key($key): string
{
    $key = strtolower((string) $key);
    return (string) preg_replace('/[^a-z0-9_\-]/', '', $key);
}

function wp_verify_nonce($nonce, $action)
{
    return $nonce === 'valid-' . $action ? 1 : false;
}

function __($text, $domain = null): string
{
    return (string) $text;
}

function register_rest_route($namespace, $route, $definition, $override = false): bool
{
    $GLOBALS['uc_test_routes'][] = array(
        'namespace' => $namespace,
        'route' => $route,
        'definition' => $definition,
        'override' => $override,
    );
    return true;
}

require_once __DIR__ . '/../packages/ultimate-commerce-for-woocommerce/src/Security/Capabilities.php';
require_once __DIR__ . '/../packages/ultimate-commerce-for-woocommerce/src/Security/Authorization.php';
require_once __DIR__ . '/../packages/ultimate-commerce-for-woocommerce/src/Security/Csrf.php';
require_once __DIR__ . '/../packages/ultimate-commerce-for-woocommerce/src/Rest/RouteRegistrar.php';

use BadOtter\UltimateCommerce\Rest\RouteRegistrar;
use BadOtter\UltimateCommerce\Security\Authorization;
use BadOtter\UltimateCommerce\Security\Capabilities;
use BadOtter\UltimateCommerce\Security\Csrf;

function uc_security_assert(bool $condition, string $message): void
{
    if ($condition) {
        return;
    }

    fwrite(STDERR, "FAIL: {$message}\n");
    exit(1);
}

Capabilities::maybeInstall();
$adminCaps = $GLOBALS['uc_test_roles']['administrator']->capabilities;
$shopCaps = $GLOBALS['uc_test_roles']['shop_manager']->capabilities;
uc_security_assert(isset($adminCaps[Capabilities::VIEW_DIAGNOSTICS]), 'administrator should receive diagnostics capability');
uc_security_assert(isset($adminCaps[Capabilities::MANAGE_SETTINGS]), 'administrator should receive settings capability');
uc_security_assert(isset($shopCaps[Capabilities::VIEW_DIAGNOSTICS]), 'shop manager should receive diagnostics capability');
uc_security_assert(!isset($shopCaps[Capabilities::MANAGE_SETTINGS]), 'shop manager should not receive settings capability by default');
uc_security_assert(($GLOBALS['uc_test_options']['uc_capability_version'] ?? '') === '1', 'capability version should be stored');

$denied = Authorization::requireCapability(Capabilities::MANAGE_SETTINGS);
uc_security_assert($denied instanceof WP_Error && $denied->code === 'uc_forbidden' && $denied->data['status'] === 403, 'missing capability should fail closed');
$GLOBALS['uc_test_caps'][Capabilities::MANAGE_SETTINGS] = true;
uc_security_assert(Authorization::requireCapability(Capabilities::MANAGE_SETTINGS) === true, 'granted capability should pass');

$GLOBALS['uc_test_user_id'] = 42;
uc_security_assert(Authorization::owns(42), 'matching owner should pass');
uc_security_assert(!Authorization::owns(43), 'different owner should fail');
$ownerDenied = Authorization::requireOwnership(43);
uc_security_assert($ownerDenied instanceof WP_Error && $ownerDenied->code === 'uc_object_forbidden', 'ownership helper should return a forbidden error');
uc_security_assert(Authorization::requireAuthenticated() === true, 'logged-in user should pass authentication helper');
$GLOBALS['uc_test_user_id'] = 0;
$authDenied = Authorization::requireAuthenticated();
uc_security_assert($authDenied instanceof WP_Error && $authDenied->data['status'] === 401, 'guest should receive authentication-required error');

uc_security_assert(Csrf::verify('valid-uc_settings_save', 'settings_save'), 'valid purpose-bound nonce should pass');
uc_security_assert(!Csrf::verify('wrong', 'settings_save'), 'invalid nonce should fail');
$csrfDenied = Csrf::require('wrong', 'settings_save');
uc_security_assert($csrfDenied instanceof WP_Error && $csrfDenied->code === 'uc_invalid_nonce', 'CSRF helper should fail closed');

$threw = false;
try {
    RouteRegistrar::register('/missing-permission', array(
        'methods' => 'GET',
        'callback' => static fn() => null,
    ));
} catch (InvalidArgumentException $exception) {
    $threw = true;
}
uc_security_assert($threw, 'REST route without permission_callback must be rejected');

$threw = false;
try {
    RouteRegistrar::register('/missing-type', array(
        'methods' => 'GET',
        'callback' => static fn() => null,
        'permission_callback' => static fn() => true,
        'args' => array('page' => array('required' => false)),
    ));
} catch (InvalidArgumentException $exception) {
    $threw = true;
}
uc_security_assert($threw, 'REST arg without type must be rejected');

$page = RouteRegistrar::pageArg(1, 10);
$perPage = RouteRegistrar::perPageArg(20, 50);
uc_security_assert(($page['validate_callback'])(10), 'page maximum should validate');
uc_security_assert(!($page['validate_callback'])(11), 'page above maximum should fail');
uc_security_assert(($perPage['validate_callback'])(50), 'per-page maximum should validate');
uc_security_assert(!($perPage['validate_callback'])(51), 'per-page above maximum should fail');

uc_security_assert(RouteRegistrar::register('/diagnostics', array(
    'methods' => 'GET',
    'callback' => static fn() => array('ok' => true),
    'permission_callback' => static fn() => true,
    'args' => array(
        'page' => $page,
        'per_page' => $perPage,
    ),
)), 'valid strict REST route should register');
$route = $GLOBALS['uc_test_routes'][0];
uc_security_assert($route['namespace'] === 'ultimate-commerce/v1', 'REST namespace must remain canonical');
uc_security_assert($route['route'] === '/diagnostics', 'REST route should be normalized');
uc_security_assert($route['override'] === true, 'UC registrar should use the explicit route definition');

fwrite(STDOUT, "Security request foundation tests passed\n");
