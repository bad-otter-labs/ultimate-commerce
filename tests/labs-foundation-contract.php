<?php

declare(strict_types=1);

define('ABSPATH', __DIR__ . '/');

function sanitize_key($key): string
{
    return (string) preg_replace('/[^a-z0-9_-]/', '', strtolower((string) $key));
}
function get_option($key, $default = false)
{
    return $default;
}
function do_action($tag, ...$args): void
{
    // Module registration must not call a remote service.
}
function __($message, $domain = ''): string
{
    return (string) $message;
}

require_once __DIR__ . '/../packages/ultimate-commerce-for-woocommerce/src/Contracts/Module.php';
require_once __DIR__ . '/../packages/ultimate-commerce-for-woocommerce/src/Contracts/AbstractModule.php';
require_once __DIR__ . '/../packages/ultimate-commerce-for-woocommerce/src/Support/Settings.php';
require_once __DIR__ . '/../packages/ultimate-commerce-for-woocommerce/src/Support/ModuleRegistry.php';
require_once __DIR__ . '/../packages/ultimate-commerce-for-woocommerce/src/Compatibility/LabsModuleMap.php';

use BadOtter\UltimateCommerce\Compatibility\LabsModuleMap;
use BadOtter\UltimateCommerce\Contracts\AbstractModule;
use BadOtter\UltimateCommerce\Contracts\Module;
use BadOtter\UltimateCommerce\Support\ModuleRegistry;

function labs_expect(bool $ok, string $why): void
{
    if (!$ok) {
        throw new RuntimeException($why);
    }
}

final class LabsFixtureModule extends AbstractModule
{
    /** @param list<string> $dependencies */
    public function __construct(
        private string $moduleKey,
        private string $legacyProduct,
        private array $dependencies = array()
    ) {
    }

    public function key(): string { return $this->moduleKey; }
    public function name(): string { return ucfirst(str_replace('_', ' ', $this->moduleKey)); }
    public function product(): string { return $this->legacyProduct; }
    public function tier(): string { return $this->legacyProduct === 'ultimate-commerce-pro' ? Module::TIER_PRO : Module::TIER_FREE; }
    public function dependencies(): array { return $this->dependencies; }
    public function register(): void {}
}

$products = array(
    'bad-otter-ultimate-commerce-woocommerce' => array(
        'product_id' => 'ultimate-commerce-for-woocommerce',
        'display_name' => 'Ultimate Commerce for WooCommerce',
        'version' => '0.2.2',
        'entitlement_id' => null,
        'requires' => array(),
    ),
    'ultimate-commerce-pro' => array(
        'product_id' => 'ultimate-commerce-pro',
        'display_name' => 'Ultimate Commerce Pro',
        'version' => '0.2.5',
        'entitlement_id' => 'ultimate-commerce-pro-core',
        'requires' => array('ultimate-commerce-for-woocommerce/core'),
    ),
);
$registry = new ModuleRegistry();
labs_expect($registry->register(new LabsFixtureModule('product_display', 'bad-otter-ultimate-commerce-woocommerce')), 'Free fixture must register');
labs_expect($registry->register(new LabsFixtureModule('cart', 'bad-otter-ultimate-commerce-woocommerce', array('product_display'))), 'Free dependency must register');
labs_expect($registry->register(new LabsFixtureModule('pro_bootstrap', 'ultimate-commerce-pro')), 'Pro fixture must register');
labs_expect($registry->register(new LabsFixtureModule('pro_recommendations', 'ultimate-commerce-pro', array('cart', 'pro_bootstrap'))), 'Pro cross-product dependencies must register');
labs_expect($registry->register(new LabsFixtureModule('outside', 'third-party-extension')), 'Unmapped extension must remain a valid UC module');

$all = LabsModuleMap::declarations($registry, $products);
$mapped = array_column($all, null, 'module_id');
labs_expect(count($mapped) === 6, 'Only explicit Free/Pro module identities plus cores should export');
labs_expect(array_keys($mapped) === array(
    'ultimate-commerce-for-woocommerce/cart',
    'ultimate-commerce-for-woocommerce/core',
    'ultimate-commerce-for-woocommerce/product-display',
    'ultimate-commerce-pro/core',
    'ultimate-commerce-pro/pro-bootstrap',
    'ultimate-commerce-pro/pro-recommendations',
), 'Stable, sorted Labs identifiers should not alter UC keys');

$core = json_decode((string) file_get_contents(__DIR__ . '/../packages/ultimate-commerce-for-woocommerce/labs-superpowers.module.json'), true, 32, JSON_THROW_ON_ERROR);
labs_expect($mapped['ultimate-commerce-for-woocommerce/core'] === $core, 'Packaged Free manifest and runtime mapping must agree exactly');
labs_expect($mapped['ultimate-commerce-for-woocommerce/cart']['legacy_module_id'] === 'cart', 'Legacy Free module key must be preserved');
labs_expect($mapped['ultimate-commerce-for-woocommerce/cart']['requires'] === array(
    'ultimate-commerce-for-woocommerce/core', 'ultimate-commerce-for-woocommerce/product-display'
), 'Free dependency must map within its own product');
labs_expect($mapped['ultimate-commerce-pro/pro-recommendations']['requires'] === array(
    'ultimate-commerce-pro/core', 'ultimate-commerce-for-woocommerce/cart', 'ultimate-commerce-pro/pro-bootstrap'
), 'Pro dependencies must resolve by the actual UC registry owner, never by guessing a prefix');
labs_expect($mapped['ultimate-commerce-pro/core']['entitlement_id'] === 'ultimate-commerce-pro-core', 'Pro commercial entitlement must not change');
labs_expect($mapped['ultimate-commerce-pro/pro-bootstrap']['entitlement_id'] === 'ultimate-commerce-pro-core', 'Pro module entitlements must remain the existing single entitlement');
foreach ($mapped as $declaration) {
    labs_expect($declaration['schema'] === LabsModuleMap::SCHEMA, 'Every declaration must retain the shared schema');
    labs_expect($declaration['capabilities'] === array() && $declaration['authorities'] === array(), 'Discovery must not mint staff permissions or data authority');
    labs_expect($declaration['optional_integrations'] === array(), 'Foundation integration must remain absent by default');
}
$freeOnly = LabsModuleMap::declarations($registry, array('bad-otter-ultimate-commerce-woocommerce' => $products['bad-otter-ultimate-commerce-woocommerce']));
labs_expect(count($freeOnly) === 3, 'Standalone Free discovery must work with no Pro descriptor');

$collision = new ModuleRegistry();
$collision->register(new LabsFixtureModule('same_a', 'bad-otter-ultimate-commerce-woocommerce'));
$collision->register(new LabsFixtureModule('same-a', 'bad-otter-ultimate-commerce-woocommerce'));
try {
    LabsModuleMap::declarations($collision, $products);
    throw new RuntimeException('Labs underscore/dash collision silently accepted');
} catch (InvalidArgumentException $expected) {
    labs_expect($expected->getMessage() === 'Ambiguous Labs module identity', 'Collision must fail closed');
}
$missing = new ModuleRegistry();
$missing->register(new LabsFixtureModule('needs_missing', 'bad-otter-ultimate-commerce-woocommerce', array('other-module')));
try {
    LabsModuleMap::declarations($missing, $products);
    throw new RuntimeException('Unmapped required dependency silently accepted');
} catch (InvalidArgumentException $expected) {
    labs_expect($expected->getMessage() === 'Unmapped required UC module dependency', 'Unknown required dependency must fail closed');
}

labs_expect(!LabsModuleMap::registerInto(new stdClass(), $all), 'Unverified optional foundation registry must not be used');

final class LabsSyntheticFoundation
{
    public array $registered = array();
    public function register(array $declaration): void
    {
        if (isset($this->registered[$declaration['module_id']])) {
            throw new InvalidArgumentException('Duplicate manifest');
        }
        $this->registered[$declaration['module_id']] = $declaration;
    }
}
class_alias(LabsSyntheticFoundation::class, 'BadOtterLabs\\SuperpowersV0\\ModuleRegistry');
$foundation = new LabsSyntheticFoundation();
labs_expect(LabsModuleMap::registerInto($foundation, $all), 'Explicit verified-foundation shape should accept manifests');
labs_expect(count($foundation->registered) === 6, 'Optional handoff must register each manifest exactly once');
labs_expect($registry->all()['cart']->key() === 'cart', 'Labs mapping must not replace UC registration');

fwrite(STDOUT, "PASS optional Labs v0 compatibility (no WordPress runtime, network, licence or stock changes)\n");
