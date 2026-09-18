<?php

declare(strict_types=1);

define('ABSPATH', __DIR__ . '/');

$ucOptions = array();
$ucRoles = array();
$ucUserMeta = array();

final class UcTestRole
{
    /** @var array<string, bool> */
    public array $caps = array();

    /** @param list<string> $caps */
    public function __construct(array $caps)
    {
        foreach ($caps as $cap) {
            $this->caps[$cap] = true;
        }
    }

    public function add_cap(string $cap): void
    {
        $this->caps[$cap] = true;
    }

    public function remove_cap(string $cap): void
    {
        unset($this->caps[$cap]);
    }
}

final class UcTestWpdb
{
    public string $options = 'wp_options';
    private string $lastLike = '';

    public function esc_like(string $value): string
    {
        return $value;
    }

    public function prepare(string $query, string $like): string
    {
        $this->lastLike = $like;
        return $query;
    }

    /** @return list<string> */
    public function get_col(string $query): array
    {
        global $ucOptions;
        unset($query);

        $prefix = str_ends_with($this->lastLike, '%') ? substr($this->lastLike, 0, -1) : $this->lastLike;
        return array_values(array_filter(
            array_keys($ucOptions),
            static fn (string $name): bool => str_starts_with($name, $prefix)
        ));
    }
}

$wpdb = new UcTestWpdb();

function get_option(string $name, $default = false)
{
    global $ucOptions;
    return array_key_exists($name, $ucOptions) ? $ucOptions[$name] : $default;
}

function update_option(string $name, $value, $autoload = null): bool
{
    global $ucOptions;
    unset($autoload);
    $ucOptions[$name] = $value;
    return true;
}

function delete_option(string $name): bool
{
    global $ucOptions;
    $existed = array_key_exists($name, $ucOptions);
    unset($ucOptions[$name]);
    return $existed;
}

function delete_metadata(string $metaType, int $objectId, string $metaKey, $metaValue = '', bool $deleteAll = false): bool
{
    global $ucUserMeta;
    unset($objectId, $metaValue);

    if ($metaType !== 'user' || !$deleteAll) {
        return false;
    }

    foreach ($ucUserMeta as &$meta) {
        unset($meta[$metaKey]);
    }
    unset($meta);
    return true;
}

function get_role(string $name)
{
    global $ucRoles;
    return $ucRoles[$name] ?? null;
}

function is_multisite(): bool
{
    return false;
}

function ucAssert(bool $condition, string $message): void
{
    if (!$condition) {
        fwrite(STDERR, "FAIL: {$message}\n");
        exit(1);
    }
}

require_once __DIR__ . '/../packages/ultimate-commerce-for-woocommerce/src/Support/Settings.php';
require_once __DIR__ . '/../packages/ultimate-commerce-for-woocommerce/src/Security/Capabilities.php';
require_once __DIR__ . '/../packages/ultimate-commerce-for-woocommerce/src/Wishlist/WishlistStore.php';
require_once __DIR__ . '/../packages/ultimate-commerce-for-woocommerce/src/Privacy/Uninstall.php';

use BadOtter\UltimateCommerce\Privacy\Uninstall;
use BadOtter\UltimateCommerce\Security\Capabilities;
use BadOtter\UltimateCommerce\Support\Settings;
use BadOtter\UltimateCommerce\Wishlist\WishlistStore;

$ucUserMeta = array(
    1 => array(
        WishlistStore::BASE_META_KEY => array(10, 11),
        'third_party_user_meta' => 'keep-user-meta',
    ),
);

$ucRoles = array(
    'administrator' => new UcTestRole(array(Capabilities::VIEW_DIAGNOSTICS, Capabilities::MANAGE_SETTINGS, 'manage_options')),
    'shop_manager' => new UcTestRole(array(Capabilities::VIEW_DIAGNOSTICS, 'manage_woocommerce')),
);

$ucOptions = array(
    'uc_delete_data_on_uninstall' => false,
    'uc_modules' => array('cart' => false, 'pro_example' => true),
    'uc_secret_store_v1' => array('pro.license' => 'encrypted-value'),
    'uc_version' => '0.2.0',
    'uc_schema_version' => '1',
    'uc_capability_version' => '1',
    'uc_lock_abc' => array('lease' => 'a'),
    'uc_idem_def' => array('state' => 'completed'),
    'uc_replay_ghi' => array('lease' => 'b'),
    '_transient_uc_rl_jkl' => array('count' => 1),
    '_transient_timeout_uc_rl_jkl' => 9999999999,
    'woocommerce_currency' => 'GBP',
    'third_party_option' => 'keep-me',
);

Uninstall::run();

ucAssert(isset($ucOptions['uc_modules']), 'Merchant module preferences must be retained by default.');
ucAssert(isset($ucOptions['uc_secret_store_v1']), 'Encrypted UC secrets must be retained by default for reinstall recovery.');
ucAssert(array_key_exists(Settings::UNINSTALL_DATA_OPTION, $ucOptions), 'Retention preference must remain when merchant data is retained.');
ucAssert(!isset($ucOptions['uc_version']) && !isset($ucOptions['uc_schema_version']), 'Runtime version metadata must always be removed.');
ucAssert(!isset($ucOptions['uc_capability_version']), 'Capability version marker must always be removed.');
ucAssert(!isset($ucOptions['uc_lock_abc']) && !isset($ucOptions['uc_idem_def']) && !isset($ucOptions['uc_replay_ghi']), 'Runtime lease/idempotency/replay records must always be removed.');
ucAssert(!isset($ucOptions['_transient_uc_rl_jkl']) && !isset($ucOptions['_transient_timeout_uc_rl_jkl']), 'UC rate-limit transients must always be removed.');
ucAssert(($ucOptions['woocommerce_currency'] ?? '') === 'GBP', 'WooCommerce-owned options must never be removed.');
ucAssert(($ucOptions['third_party_option'] ?? '') === 'keep-me', 'Unrelated third-party options must never be removed.');
ucAssert(($ucUserMeta[1][WishlistStore::metaKey()] ?? array()) === array(10, 11), 'Wishlist personal data must be retained by default.');
ucAssert(($ucUserMeta[1]['third_party_user_meta'] ?? '') === 'keep-user-meta', 'Default uninstall must preserve unrelated user meta.');
ucAssert(!isset($ucRoles['administrator']->caps[Capabilities::VIEW_DIAGNOSTICS]) && !isset($ucRoles['administrator']->caps[Capabilities::MANAGE_SETTINGS]), 'Administrator UC capabilities must be removed on uninstall.');
ucAssert(!isset($ucRoles['shop_manager']->caps[Capabilities::VIEW_DIAGNOSTICS]), 'Shop manager UC capability must be removed on uninstall.');
ucAssert(isset($ucRoles['administrator']->caps['manage_options']) && isset($ucRoles['shop_manager']->caps['manage_woocommerce']), 'Non-UC role capabilities must be preserved.');

$ucRoles['administrator']->add_cap(Capabilities::VIEW_DIAGNOSTICS);
$ucRoles['administrator']->add_cap(Capabilities::MANAGE_SETTINGS);
$ucRoles['shop_manager']->add_cap(Capabilities::VIEW_DIAGNOSTICS);
Settings::updateDeleteDataOnUninstall(true);
$ucOptions['uc_version'] = '0.2.0';
$ucOptions['uc_schema_version'] = '1';
$ucOptions['uc_capability_version'] = '1';
$ucOptions['uc_lock_again'] = array();
$ucOptions['ultimate_commerce_version'] = '0.1.4';
$ucOptions['ultimate_commerce_schema_version'] = '1';
$ucOptions['ultimate_commerce_modules'] = array('legacy' => true);

Uninstall::run();

ucAssert(!isset($ucOptions['uc_modules']), 'Explicit purge must remove canonical module preferences.');
ucAssert(!isset($ucOptions['uc_secret_store_v1']), 'Explicit purge must remove the UC encrypted secret store.');
ucAssert(!isset($ucOptions[Settings::UNINSTALL_DATA_OPTION]), 'Explicit purge must remove its own uninstall preference.');
ucAssert(!isset($ucOptions['ultimate_commerce_version']) && !isset($ucOptions['ultimate_commerce_schema_version']) && !isset($ucOptions['ultimate_commerce_modules']), 'Explicit purge must remove retained legacy migration data.');
ucAssert(($ucOptions['woocommerce_currency'] ?? '') === 'GBP', 'Explicit UC purge must still preserve WooCommerce-owned options.');
ucAssert(($ucOptions['third_party_option'] ?? '') === 'keep-me', 'Explicit UC purge must still preserve unrelated third-party options.');
ucAssert(!isset($ucUserMeta[1][WishlistStore::metaKey()]), 'Explicit UC purge must remove wishlist user meta.');
ucAssert(($ucUserMeta[1]['third_party_user_meta'] ?? '') === 'keep-user-meta', 'Explicit UC purge must preserve unrelated user meta.');

Settings::updateDeleteDataOnUninstall(false);
ucAssert(Settings::deleteDataOnUninstall() === false, 'Merchant must be able to return to the default retain-on-uninstall policy.');

echo "Ultimate Commerce uninstall/data-retention contract passed.\n";
