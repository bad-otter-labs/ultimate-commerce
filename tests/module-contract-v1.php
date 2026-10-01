<?php

declare(strict_types=1);

define('ABSPATH', __DIR__ . '/');

$GLOBALS['badotulc_test_options'] = array();
$GLOBALS['badotulc_test_actions'] = array();
$GLOBALS['badotulc_test_boot_order'] = array();

function sanitize_key($key): string
{
    $key = strtolower((string) $key);
    return (string) preg_replace('/[^a-z0-9_\-]/', '', $key);
}

function get_option($name, $default = false)
{
    return $GLOBALS['badotulc_test_options'][$name] ?? $default;
}

function apply_filters($tag, $value, ...$args)
{
    return $value;
}

function do_action($tag, ...$args): void
{
    $GLOBALS['badotulc_test_actions'][] = $tag;
}

require_once __DIR__ . '/../packages/ultimate-commerce-for-woocommerce/src/Contracts/Module.php';
require_once __DIR__ . '/../packages/ultimate-commerce-for-woocommerce/src/Contracts/AbstractModule.php';
require_once __DIR__ . '/../packages/ultimate-commerce-for-woocommerce/src/Support/Settings.php';
require_once __DIR__ . '/../packages/ultimate-commerce-for-woocommerce/src/Support/ModuleRegistry.php';

use BadOtter\UltimateCommerce\Contracts\AbstractModule;
use BadOtter\UltimateCommerce\Contracts\Module;
use BadOtter\UltimateCommerce\Support\ModuleRegistry;

final class FixtureModule extends AbstractModule
{
    /** @param list<string> $dependencies */
    public function __construct(
        private string $moduleKey,
        private array $moduleDependencies = array()
    ) {
    }

    public function key(): string
    {
        return $this->moduleKey;
    }

    public function name(): string
    {
        return ucfirst(str_replace('_', ' ', $this->moduleKey));
    }

    public function product(): string
    {
        return 'fixture-extension';
    }

    public function tier(): string
    {
        return Module::TIER_EXTENSION;
    }

    public function dependencies(): array
    {
        return $this->moduleDependencies;
    }

    public function register(): void
    {
        $GLOBALS['badotulc_test_boot_order'][] = $this->moduleKey;
    }
}

function badotulc_assert(bool $condition, string $message): void
{
    if ($condition) {
        return;
    }

    fwrite(STDERR, "FAIL: {$message}\n");
    exit(1);
}

badotulc_assert(Module::CONTRACT_VERSION === '1.0.0', 'module contract version changed unexpectedly');

$registry = new ModuleRegistry();
badotulc_assert($registry->register(new FixtureModule('dependent', array('base'))), 'dependent module should register');
badotulc_assert($registry->register(new FixtureModule('base')), 'base module should register');
badotulc_assert(!$registry->register(new FixtureModule('base')), 'duplicate key should be rejected');
$registry->boot();
badotulc_assert($GLOBALS['badotulc_test_boot_order'] === array('base', 'dependent'), 'dependencies should boot before dependants');
badotulc_assert($registry->isBooted('base'), 'base should boot');
badotulc_assert($registry->isBooted('dependent'), 'dependent should boot');
$status = $registry->statuses()['dependent'];
badotulc_assert($status['product'] === 'fixture-extension', 'product metadata should be exposed');
badotulc_assert($status['tier'] === Module::TIER_EXTENSION, 'tier metadata should be exposed');
badotulc_assert($status['compatibility']['module_api'] === '1.0.0', 'module API compatibility should be exposed');

$GLOBALS['badotulc_test_boot_order'] = array();
$missing = new ModuleRegistry();
badotulc_assert($missing->register(new FixtureModule('needs_missing', array('not_registered'))), 'module with missing dependency should register before boot resolution');
$missing->boot();
badotulc_assert(!$missing->isBooted('needs_missing'), 'missing dependency should block boot');
badotulc_assert($missing->issues()['needs_missing'] === 'missing_dependency:not_registered', 'missing dependency reason should be inspectable');

$cycle = new ModuleRegistry();
badotulc_assert($cycle->register(new FixtureModule('cycle_a', array('cycle_b'))), 'cycle_a should register');
badotulc_assert($cycle->register(new FixtureModule('cycle_b', array('cycle_a'))), 'cycle_b should register');
$cycle->boot();
badotulc_assert(!$cycle->isBooted('cycle_a') && !$cycle->isBooted('cycle_b'), 'circular dependencies must not boot');
badotulc_assert(isset($cycle->issues()['cycle_a']) || isset($cycle->issues()['cycle_b']), 'circular dependency should expose an issue');

$GLOBALS['badotulc_test_options']['badotulc_modules'] = array('disabled_base' => false);
$disabled = new ModuleRegistry();
badotulc_assert($disabled->register(new FixtureModule('disabled_base')), 'disabled dependency should register');
badotulc_assert($disabled->register(new FixtureModule('needs_disabled', array('disabled_base'))), 'dependent on disabled module should register');
$disabled->boot();
badotulc_assert(!$disabled->isBooted('disabled_base'), 'disabled module must not boot');
badotulc_assert(!$disabled->isBooted('needs_disabled'), 'dependant of disabled module must not boot');
badotulc_assert($disabled->statuses()['disabled_base']['status'] === 'disabled', 'disabled status should be explicit');

fwrite(STDOUT, "Module contract v1 tests passed\n");
