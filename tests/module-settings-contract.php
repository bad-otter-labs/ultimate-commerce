<?php

declare(strict_types=1);

define('ABSPATH', __DIR__ . '/');

$ucTestOptions = array(
    'ulticofo_modules' => array(
        'stale_extension' => false,
    ),
);
$ucTestEvents = array();

function sanitize_key($key): string
{
    $key = strtolower((string) $key);
    return (string) preg_replace('/[^a-z0-9_\-]/', '', $key);
}

function get_option(string $key, $default = false)
{
    global $ucTestOptions;
    return array_key_exists($key, $ucTestOptions) ? $ucTestOptions[$key] : $default;
}

function update_option(string $key, $value, $autoload = null): bool
{
    global $ucTestOptions;
    $ucTestOptions[$key] = $value;
    return true;
}

function apply_filters(string $hook, $value, ...$args)
{
    return $value;
}

function do_action(string $hook, ...$args): void
{
    global $ucTestEvents;
    $ucTestEvents[] = array($hook, $args);
}

function __(string $text, string $domain = ''): string
{
    return $text;
}

function ucAssert(bool $condition, string $message): void
{
    if (!$condition) {
        fwrite(STDERR, "FAIL: {$message}\n");
        exit(1);
    }
}

require_once __DIR__ . '/../packages/ultimate-commerce-for-woocommerce/src/Contracts/Module.php';
require_once __DIR__ . '/../packages/ultimate-commerce-for-woocommerce/src/Contracts/AbstractModule.php';
require_once __DIR__ . '/../packages/ultimate-commerce-for-woocommerce/src/Support/Settings.php';
require_once __DIR__ . '/../packages/ultimate-commerce-for-woocommerce/src/Support/ModuleRegistry.php';

final class UcModuleSettingsFixture extends \BadOtter\UltimateCommerce\Contracts\AbstractModule
{
    private string $moduleKey;
    /** @var list<string> */
    private array $moduleDependencies;

    /** @param list<string> $dependencies */
    public function __construct(string $key, array $dependencies = array())
    {
        $this->moduleKey = $key;
        $this->moduleDependencies = $dependencies;
    }

    public function key(): string
    {
        return $this->moduleKey;
    }

    public function name(): string
    {
        return 'Fixture ' . $this->moduleKey;
    }

    public function product(): string
    {
        return 'ultimate-commerce-for-woocommerce';
    }

    public function tier(): string
    {
        return \BadOtter\UltimateCommerce\Contracts\Module::TIER_FREE;
    }

    public function dependencies(): array
    {
        return $this->moduleDependencies;
    }

    public function register(): void
    {
        do_action('uc_fixture_registered', $this->moduleKey);
    }
}

use BadOtter\UltimateCommerce\Support\ModuleRegistry;
use BadOtter\UltimateCommerce\Support\Settings;

ucAssert(Settings::moduleEnabled('alpha') === true, 'Unconfigured modules must default to enabled.');

$stored = Settings::updateModuleStates(array(
    'alpha' => false,
    'Bad Key' => false,
));
ucAssert(isset($stored['alpha']) && $stored['alpha'] === false, 'Explicit disabled state must be persisted.');
ucAssert(isset($stored['stale_extension']) && $stored['stale_extension'] === false, 'Unregistered extension state must be preserved.');
ucAssert(!array_key_exists('badkey', $stored), 'Non-canonical module keys must not be persisted.');

$registry = new ModuleRegistry();
ucAssert($registry->register(new UcModuleSettingsFixture('alpha')) === true, 'Alpha module should register.');
ucAssert($registry->register(new UcModuleSettingsFixture('beta', array('alpha'))) === true, 'Beta module should register.');
$registry->boot();
$statuses = $registry->statuses();
ucAssert($statuses['alpha']['enabled'] === false, 'Stored alpha preference must drive effective state.');
ucAssert($statuses['alpha']['status'] === 'disabled', 'Disabled module must report disabled runtime status.');
ucAssert($statuses['beta']['enabled'] === true, 'Unconfigured beta module must remain enabled by default.');
ucAssert($statuses['beta']['status'] === 'blocked', 'Enabled dependent module must block when dependency is unavailable.');
ucAssert($statuses['beta']['issue'] === 'dependency_unavailable:alpha', 'Blocked module must expose its dependency reason.');

$stored = Settings::updateModuleStates(array(
    'alpha' => true,
    'beta' => false,
));
ucAssert($stored['stale_extension'] === false, 'Saving registered modules must not erase temporarily unregistered states.');

$registry = new ModuleRegistry();
$registry->register(new UcModuleSettingsFixture('alpha'));
$registry->register(new UcModuleSettingsFixture('beta', array('alpha')));
$registry->boot();
$statuses = $registry->statuses();
ucAssert($statuses['alpha']['booted'] === true, 'Re-enabled dependency must boot on the next registry lifecycle.');
ucAssert($statuses['beta']['status'] === 'disabled', 'Explicitly disabled dependent module must remain disabled.');

echo "Ultimate Commerce module settings contract passed.\n";
