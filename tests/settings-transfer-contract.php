<?php

declare(strict_types=1);

define('ABSPATH', __DIR__ . '/');

$ucOptions = array();

class WP_Error
{
    private string $code;
    private string $message;
    private array $data;

    public function __construct(string $code = '', string $message = '', array $data = array())
    {
        $this->code = $code;
        $this->message = $message;
        $this->data = $data;
    }

    public function get_error_code(): string
    {
        return $this->code;
    }

    public function get_error_message(): string
    {
        return $this->message;
    }
}

function __(string $text, string $domain = ''): string
{
    return $text;
}

function sanitize_key(string $key): string
{
    $key = strtolower($key);
    return preg_replace('/[^a-z0-9_\-]/', '', $key) ?? '';
}

function get_option(string $name, $default = false)
{
    global $ucOptions;
    return array_key_exists($name, $ucOptions) ? $ucOptions[$name] : $default;
}

function update_option(string $name, $value, $autoload = null): bool
{
    global $ucOptions;
    $ucOptions[$name] = $value;
    return true;
}

function apply_filters(string $hook, $value, ...$args)
{
    return $value;
}

function wp_json_encode($value, int $flags = 0, int $depth = 512)
{
    return json_encode($value, $flags, $depth);
}

function ucAssert(bool $condition, string $message): void
{
    if (!$condition) {
        fwrite(STDERR, "FAIL: {$message}\n");
        exit(1);
    }
}

function ucImportError(string $json, string $expectedCode): void
{
    global $ucOptions;
    $before = $ucOptions;
    $result = BadOtter\UltimateCommerce\Support\SettingsTransfer::importJson($json);
    ucAssert($result instanceof WP_Error, "Expected import error {$expectedCode}.");
    ucAssert($result->get_error_code() === $expectedCode, "Unexpected import error code for {$expectedCode}.");
    ucAssert($ucOptions === $before, "Failed import {$expectedCode} must not mutate settings.");
}

require_once __DIR__ . '/../packages/ultimate-commerce-for-woocommerce/src/Support/Settings.php';
require_once __DIR__ . '/../packages/ultimate-commerce-for-woocommerce/src/Support/SettingsTransfer.php';

use BadOtter\UltimateCommerce\Support\SettingsTransfer;

$ucOptions = array(
    'ulticofo_modules' => array(
        'cart' => true,
        'extension_absent' => false,
        'Bad Key' => true,
    ),
    'ulticofo_version' => 'should-not-export',
    'ulticofo_schema_version' => 'should-not-export',
);

$export = SettingsTransfer::exportJson();
ucAssert(is_string($export), 'Export must produce JSON text.');
$decoded = json_decode($export);
ucAssert($decoded instanceof stdClass, 'Export must decode to a JSON object.');
ucAssert($decoded->format === 'ultimate-commerce-settings', 'Export format identifier mismatch.');
ucAssert($decoded->schema_version === 1, 'Export schema version mismatch.');
ucAssert($decoded->product === 'ultimate-commerce-for-woocommerce', 'Export product identity mismatch.');
ucAssert($decoded->settings instanceof stdClass, 'Export settings must be an object.');
ucAssert($decoded->settings->modules instanceof stdClass, 'Export modules must be an object.');
ucAssert($decoded->settings->modules->cart === true, 'Stored cart preference missing from export.');
ucAssert($decoded->settings->modules->extension_absent === false, 'Temporarily absent extension preference missing from export.');
ucAssert(!property_exists($decoded->settings->modules, 'Bad Key'), 'Invalid stored module keys must not be exported.');
ucAssert(strpos($export, 'ulticofo_version') === false, 'Runtime version metadata must not be exported.');
ucAssert(strpos($export, 'ulticofo_schema_version') === false, 'Runtime schema metadata must not be exported.');

$valid = json_encode(array(
    'format' => 'ultimate-commerce-settings',
    'schema_version' => 1,
    'product' => 'ultimate-commerce-for-woocommerce',
    'settings' => array(
        'modules' => (object) array(
            'cart' => false,
            'product_display' => true,
        ),
    ),
));
$result = SettingsTransfer::importJson((string) $valid);
ucAssert(is_array($result), 'Valid import must return merged module states.');
ucAssert($ucOptions['ulticofo_modules']['cart'] === false, 'Import must update declared module preferences.');
ucAssert($ucOptions['ulticofo_modules']['product_display'] === true, 'Import must add declared canonical module preferences.');
ucAssert($ucOptions['ulticofo_modules']['extension_absent'] === false, 'Import must preserve omitted extension preferences.');

$emptyModules = json_encode(array(
    'format' => 'ultimate-commerce-settings',
    'schema_version' => 1,
    'product' => 'ultimate-commerce-for-woocommerce',
    'settings' => array('modules' => (object) array()),
));
$beforeEmpty = $ucOptions;
$emptyResult = SettingsTransfer::importJson((string) $emptyModules);
ucAssert(is_array($emptyResult), 'Empty module object must be a valid no-op import.');
ucAssert($ucOptions === $beforeEmpty, 'Empty module import must preserve all existing preferences.');

ucImportError('', 'uc_settings_import_size');
ucImportError(str_repeat('x', SettingsTransfer::MAX_BYTES + 1), 'uc_settings_import_size');
ucImportError('{not json}', 'uc_settings_import_json');

$wrongShape = json_encode(array(
    'format' => 'ultimate-commerce-settings',
    'schema_version' => 1,
    'product' => 'ultimate-commerce-for-woocommerce',
    'settings' => array('modules' => (object) array()),
    'extra' => true,
));
ucImportError((string) $wrongShape, 'uc_settings_import_shape');

$wrongProduct = json_encode(array(
    'format' => 'ultimate-commerce-settings',
    'schema_version' => 1,
    'product' => 'ultimate-commerce-pro',
    'settings' => array('modules' => (object) array()),
));
ucImportError((string) $wrongProduct, 'uc_settings_import_product');

$wrongSchema = json_encode(array(
    'format' => 'ultimate-commerce-settings',
    'schema_version' => 2,
    'product' => 'ultimate-commerce-for-woocommerce',
    'settings' => array('modules' => (object) array()),
));
ucImportError((string) $wrongSchema, 'uc_settings_import_schema');

$unknownSettings = json_encode(array(
    'format' => 'ultimate-commerce-settings',
    'schema_version' => 1,
    'product' => 'ultimate-commerce-for-woocommerce',
    'settings' => array(
        'modules' => (object) array(),
        'secrets' => (object) array('token' => 'nope'),
    ),
));
ucImportError((string) $unknownSettings, 'uc_settings_import_settings');

$listModules = json_encode(array(
    'format' => 'ultimate-commerce-settings',
    'schema_version' => 1,
    'product' => 'ultimate-commerce-for-woocommerce',
    'settings' => array('modules' => array('cart')),
));
ucImportError((string) $listModules, 'uc_settings_import_modules');

$invalidValue = json_encode(array(
    'format' => 'ultimate-commerce-settings',
    'schema_version' => 1,
    'product' => 'ultimate-commerce-for-woocommerce',
    'settings' => array('modules' => (object) array('cart' => 1)),
));
ucImportError((string) $invalidValue, 'uc_settings_import_module_value');

$invalidKey = json_encode(array(
    'format' => 'ultimate-commerce-settings',
    'schema_version' => 1,
    'product' => 'ultimate-commerce-for-woocommerce',
    'settings' => array('modules' => (object) array('Bad Key' => true)),
));
ucImportError((string) $invalidKey, 'uc_settings_import_module_value');

$tooMany = array();
for ($index = 0; $index < 129; $index++) {
    $tooMany['module_' . $index] = true;
}
$tooManyJson = json_encode(array(
    'format' => 'ultimate-commerce-settings',
    'schema_version' => 1,
    'product' => 'ultimate-commerce-for-woocommerce',
    'settings' => array('modules' => (object) $tooMany),
));
ucImportError((string) $tooManyJson, 'uc_settings_import_module_limit');

echo "Ultimate Commerce settings transfer contract passed.\n";
