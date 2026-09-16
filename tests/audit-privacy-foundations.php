<?php

declare(strict_types=1);

define('ABSPATH', __DIR__ . '/');

final class WP_Error
{
    public function __construct(public string $code, public string $message, public array $data = array())
    {
    }
}

$GLOBALS['uc_governance_filters'] = array();
$GLOBALS['uc_governance_actions'] = array();
$GLOBALS['uc_audit_events'] = array();
$GLOBALS['uc_test_user_id'] = 42;

function __($text, $domain = null): string { return (string) $text; }
function get_current_user_id(): int { return (int) $GLOBALS['uc_test_user_id']; }
function wp_generate_uuid4(): string { return '123e4567-e89b-42d3-a456-426614174000'; }

function add_filter($hook, $callback, $priority = 10, $acceptedArgs = 1): bool
{
    $GLOBALS['uc_governance_filters'][$hook][$priority][] = $callback;
    return true;
}

function apply_filters($hook, $value, ...$args)
{
    $callbacks = $GLOBALS['uc_governance_filters'][$hook] ?? array();
    ksort($callbacks);
    foreach ($callbacks as $priorityCallbacks) {
        foreach ($priorityCallbacks as $callback) {
            $value = $callback($value, ...$args);
        }
    }
    return $value;
}

function add_action($hook, $callback, $priority = 10, $acceptedArgs = 1): bool
{
    $GLOBALS['uc_governance_actions'][$hook][$priority][] = $callback;
    return true;
}

function do_action($hook, ...$args): void
{
    $callbacks = $GLOBALS['uc_governance_actions'][$hook] ?? array();
    ksort($callbacks);
    foreach ($callbacks as $priorityCallbacks) {
        foreach ($priorityCallbacks as $callback) {
            $callback(...$args);
        }
    }
    if ($hook === 'uc_audit_event' && isset($args[0])) {
        $GLOBALS['uc_audit_events'][] = $args[0];
    }
}

require_once __DIR__ . '/../packages/ultimate-commerce-for-woocommerce/src/Privacy/DataClassification.php';
require_once __DIR__ . '/../packages/ultimate-commerce-for-woocommerce/src/Privacy/DataRetention.php';
require_once __DIR__ . '/../packages/ultimate-commerce-for-woocommerce/src/Privacy/PersonalDataRegistry.php';
require_once __DIR__ . '/../packages/ultimate-commerce-for-woocommerce/src/Audit/AuditEvent.php';
require_once __DIR__ . '/../packages/ultimate-commerce-for-woocommerce/src/Contracts/AuditSink.php';
require_once __DIR__ . '/../packages/ultimate-commerce-for-woocommerce/src/Audit/HookAuditSink.php';
require_once __DIR__ . '/../packages/ultimate-commerce-for-woocommerce/src/Audit/Audit.php';

use BadOtter\UltimateCommerce\Audit\Audit;
use BadOtter\UltimateCommerce\Audit\AuditEvent;
use BadOtter\UltimateCommerce\Privacy\DataClassification;
use BadOtter\UltimateCommerce\Privacy\DataRetention;
use BadOtter\UltimateCommerce\Privacy\PersonalDataRegistry;

function uc_governance_assert(bool $condition, string $message): void
{
    if ($condition) {
        return;
    }
    fwrite(STDERR, "FAIL: {$message}\n");
    exit(1);
}

uc_governance_assert(DataClassification::all() === array('public', 'merchant_operational', 'personal', 'credential_secret'), 'data classification vocabulary must remain canonical');
uc_governance_assert(DataClassification::valid(DataClassification::PERSONAL), 'personal classification must be valid');
uc_governance_assert(!DataClassification::valid('private-ish'), 'unknown data classification must be rejected');
uc_governance_assert(DataRetention::valid(DataRetention::OPERATIONAL_HISTORY), 'operational-history retention must be valid');

$event = AuditEvent::forCurrentUser(
    'inventory.adjusted',
    'variation',
    '123',
    'damaged_stock',
    array('quantity_delta' => -2, 'location_id' => 7)
);
uc_governance_assert($event instanceof AuditEvent, 'valid current-user audit event should be created');
$payload = $event->toArray();
uc_governance_assert($payload['actor_type'] === 'user' && $payload['actor_id'] === '42', 'audit actor must use current WordPress user ID');
uc_governance_assert($payload['classification'] === DataClassification::MERCHANT_OPERATIONAL, 'audit event classification must be merchant operational');
uc_governance_assert($payload['retention'] === DataRetention::OPERATIONAL_HISTORY, 'audit event retention must be operational history');
uc_governance_assert(($payload['context']['quantity_delta'] ?? null) === -2, 'safe scalar audit context should be retained');

$sensitive = AuditEvent::create('integration.changed', 'user', '42', 'integration', 'carrier', '', array('api_key' => 'do-not-log'));
uc_governance_assert($sensitive instanceof WP_Error && $sensitive->code === 'uc_audit_context_sensitive', 'secret-like audit context keys must fail closed');
$nested = AuditEvent::create('inventory.adjusted', 'user', '42', 'variation', '123', '', array('request' => array('unsafe')));
uc_governance_assert($nested instanceof WP_Error && $nested->code === 'uc_audit_context_invalid', 'nested audit context must be rejected');
$badUser = AuditEvent::create('inventory.adjusted', 'user', 'email@example.com', 'variation', '123');
uc_governance_assert($badUser instanceof WP_Error && $badUser->code === 'uc_audit_actor_invalid', 'user actor must use numeric WordPress ID rather than PII');
uc_governance_assert(Audit::record($event) === true, 'default audit sink should accept event');
uc_governance_assert(count($GLOBALS['uc_audit_events']) === 1, 'default audit sink should emit uc_audit_event hook');

add_action('uc_register_personal_data_handlers', static function (PersonalDataRegistry $registry): void {
    $result = $registry->register(
        'stock_alerts',
        'Ultimate Commerce stock alerts',
        static function (string $email, int $page = 1): array {
            return array(
                'data' => array(array(
                    'group_id' => 'ultimate-commerce-stock-alerts',
                    'group_label' => 'Ultimate Commerce stock alerts',
                    'item_id' => 'alert-1',
                    'data' => array(array('name' => 'Alert reference', 'value' => '1')),
                )),
                'done' => true,
            );
        },
        static function (string $email, int $page = 1): array {
            return array(
                'items_removed' => true,
                'items_retained' => false,
                'messages' => array(),
                'done' => true,
            );
        },
        DataRetention::ACCOUNT_LIFETIME
    );
    uc_governance_assert($result === true, 'personal-data handler should register');
});

PersonalDataRegistry::hooks();
$exporters = apply_filters('wp_privacy_personal_data_exporters', array());
$erasers = apply_filters('wp_privacy_personal_data_erasers', array());
uc_governance_assert(isset($exporters['ultimate-commerce-stock_alerts']), 'UC personal-data exporter must register through WordPress privacy filter');
uc_governance_assert(isset($erasers['ultimate-commerce-stock_alerts']), 'UC personal-data eraser must register through WordPress privacy filter');
$exportResult = ($exporters['ultimate-commerce-stock_alerts']['callback'])('customer@example.com', 1);
uc_governance_assert(($exportResult['done'] ?? false) === true, 'registered exporter callback should remain callable');
$eraseResult = ($erasers['ultimate-commerce-stock_alerts']['callback'])('customer@example.com', 1);
uc_governance_assert(($eraseResult['items_removed'] ?? false) === true, 'registered eraser callback should remain callable');
$metadata = PersonalDataRegistry::instance()->metadata();
uc_governance_assert(($metadata['stock_alerts']['classification'] ?? '') === DataClassification::PERSONAL, 'personal-data handler metadata must classify data as personal');
uc_governance_assert(($metadata['stock_alerts']['retention'] ?? '') === DataRetention::ACCOUNT_LIFETIME, 'personal-data handler retention metadata must be preserved');

$duplicate = PersonalDataRegistry::instance()->register(
    'stock_alerts',
    'Duplicate',
    static fn(string $email, int $page = 1): array => array('data' => array(), 'done' => true),
    static fn(string $email, int $page = 1): array => array('items_removed' => false, 'items_retained' => false, 'messages' => array(), 'done' => true)
);
uc_governance_assert($duplicate instanceof WP_Error && $duplicate->code === 'uc_privacy_handler_duplicate', 'duplicate personal-data handler keys must fail closed');

fwrite(STDOUT, "Audit and privacy foundation tests passed\n");
