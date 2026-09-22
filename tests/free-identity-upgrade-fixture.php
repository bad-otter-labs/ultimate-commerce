<?php

declare(strict_types=1);

define('ABSPATH', __DIR__ . '/');

$GLOBALS['uc_upgrade_options'] = array(
    'ultimate_commerce_version' => '0.1.4',
    'ultimate_commerce_schema_version' => '1',
    'ultimate_commerce_modules' => array(
        'products' => true,
        'variations' => true,
        'cart' => false,
        'account' => true,
    ),
    'woocommerce_store_address' => 'Unchanged Woo address',
    'woocommerce_currency' => 'GBP',
);
$GLOBALS['uc_upgrade_writes'] = array();

function get_option(string $name, $default = false)
{
    return array_key_exists($name, $GLOBALS['uc_upgrade_options'])
        ? $GLOBALS['uc_upgrade_options'][$name]
        : $default;
}

function add_option(string $name, $value = '', string $deprecated = '', $autoload = null): bool
{
    $GLOBALS['uc_upgrade_writes'][] = array(
        'name' => $name,
        'value' => $value,
        'deprecated' => $deprecated,
        'autoload' => $autoload,
    );

    if (array_key_exists($name, $GLOBALS['uc_upgrade_options'])) {
        return false;
    }

    $GLOBALS['uc_upgrade_options'][$name] = $value;
    return true;
}

require dirname(__DIR__) . '/packages/ultimate-commerce-for-woocommerce/src/Support/OptionMigrator.php';

use BadOtter\UltimateCommerce\Support\OptionMigrator;

function uc_upgrade_assert(bool $condition, string $message): void
{
    if (!$condition) {
        throw new RuntimeException($message);
    }
}

$legacyBefore = array(
    'ultimate_commerce_version' => $GLOBALS['uc_upgrade_options']['ultimate_commerce_version'],
    'ultimate_commerce_schema_version' => $GLOBALS['uc_upgrade_options']['ultimate_commerce_schema_version'],
    'ultimate_commerce_modules' => $GLOBALS['uc_upgrade_options']['ultimate_commerce_modules'],
);
$wooBefore = array(
    'woocommerce_store_address' => $GLOBALS['uc_upgrade_options']['woocommerce_store_address'],
    'woocommerce_currency' => $GLOBALS['uc_upgrade_options']['woocommerce_currency'],
);

OptionMigrator::migrate();

uc_upgrade_assert(
    $GLOBALS['uc_upgrade_options']['ulticofo_version'] === '0.1.4',
    'Legacy version must be copied into the canonical version option.'
);
uc_upgrade_assert(
    $GLOBALS['uc_upgrade_options']['ulticofo_schema_version'] === '1',
    'Legacy schema version must be copied into the canonical schema option.'
);
uc_upgrade_assert(
    $GLOBALS['uc_upgrade_options']['ulticofo_modules'] === $legacyBefore['ultimate_commerce_modules'],
    'Legacy module preferences must survive the identity migration without transformation.'
);

foreach ($legacyBefore as $name => $value) {
    uc_upgrade_assert(
        array_key_exists($name, $GLOBALS['uc_upgrade_options'])
            && $GLOBALS['uc_upgrade_options'][$name] === $value,
        'Legacy rollback option must be preserved: ' . $name
    );
}

foreach ($wooBefore as $name => $value) {
    uc_upgrade_assert(
        $GLOBALS['uc_upgrade_options'][$name] === $value,
        'WooCommerce-owned option changed during migration: ' . $name
    );
}

$expectedWrites = array('ulticofo_version', 'ulticofo_schema_version', 'ulticofo_modules');
uc_upgrade_assert(count($GLOBALS['uc_upgrade_writes']) === count($expectedWrites), 'Migration must write only missing canonical options.');
foreach ($GLOBALS['uc_upgrade_writes'] as $index => $write) {
    uc_upgrade_assert(($write['name'] ?? '') === $expectedWrites[$index], 'Unexpected migration write ordering or option name.');
    uc_upgrade_assert(($write['autoload'] ?? null) === false, 'Migrated options must not be newly autoloaded.');
    uc_upgrade_assert(!str_starts_with((string) ($write['name'] ?? ''), 'woocommerce_'), 'Migration must not write WooCommerce-owned options.');
}

$firstWriteCount = count($GLOBALS['uc_upgrade_writes']);
OptionMigrator::migrate();
uc_upgrade_assert(
    count($GLOBALS['uc_upgrade_writes']) === $firstWriteCount,
    'Repeated migration must be idempotent and perform no additional writes.'
);

$canonicalModules = array(
    'products' => true,
    'variations' => false,
    'cart' => true,
    'account' => true,
);
$GLOBALS['uc_upgrade_options']['ulticofo_modules'] = $canonicalModules;
$GLOBALS['uc_upgrade_options']['ultimate_commerce_modules'] = array(
    'products' => false,
    'variations' => false,
    'cart' => false,
    'account' => false,
);

OptionMigrator::migrate();
uc_upgrade_assert(
    $GLOBALS['uc_upgrade_options']['ulticofo_modules'] === $canonicalModules,
    'An existing canonical option must win over a conflicting legacy value.'
);
uc_upgrade_assert(
    count($GLOBALS['uc_upgrade_writes']) === $firstWriteCount,
    'Conflict handling must not overwrite canonical state.'
);

$root = dirname(__DIR__);
$migrator = file_get_contents($root . '/packages/ultimate-commerce-for-woocommerce/src/Support/OptionMigrator.php');
$canonicalEntry = file_get_contents($root . '/packages/ultimate-commerce-for-woocommerce/ultimate-commerce-for-woocommerce.php');
$legacyEntry = file_get_contents($root . '/packages/ultimate-commerce/ultimate-commerce.php');

uc_upgrade_assert(is_string($migrator), 'Option migrator source must be readable.');
uc_upgrade_assert(!str_contains((string) $migrator, 'delete_option('), 'Identity migration must not delete rollback data.');
uc_upgrade_assert(!str_contains((string) $migrator, 'update_option('), 'Identity migration must not overwrite existing canonical data.');
uc_upgrade_assert(!str_contains((string) $migrator, 'woocommerce_'), 'Identity migration must not target WooCommerce-owned options.');

foreach (array(
    "'ulticofo_version' => array('uc_version', 'ultimate_commerce_version')",
    "'ulticofo_schema_version' => array('uc_schema_version', 'ultimate_commerce_schema_version')",
    "'ulticofo_modules' => array('uc_modules', 'ultimate_commerce_modules')",
) as $mapping) {
    uc_upgrade_assert(str_contains((string) $migrator, $mapping), 'Expected legacy-to-canonical mapping is missing: ' . $mapping);
}

uc_upgrade_assert(
    is_string($legacyEntry) && str_contains($legacyEntry, 'Version: 0.1.4'),
    'Upgrade fixture must remain anchored to the released private 0.1.4 package.'
);
uc_upgrade_assert(
    is_string($legacyEntry) && str_contains($legacyEntry, 'Update URI: https://badotter.io/ultimate-commerce'),
    'Legacy 0.1.4 fixture must retain its historical managed-update identity.'
);
uc_upgrade_assert(
    is_string($canonicalEntry) && !str_contains($canonicalEntry, 'Update URI:'),
    'Canonical WordPress.org Free package must not inherit the legacy update override.'
);
uc_upgrade_assert(
    !is_dir($root . '/packages/ultimate-commerce-for-woocommerce/src/Updates'),
    'Canonical Free package must not contain the legacy managed updater.'
);

echo "Ultimate Commerce 0.1.4 to canonical Free data-upgrade fixture validated.\n";
