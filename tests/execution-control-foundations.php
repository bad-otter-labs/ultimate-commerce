<?php

declare(strict_types=1);

define('ABSPATH', __DIR__ . '/');

final class WP_Error
{
    public function __construct(public string $code, public string $message, public array $data = array())
    {
    }
}

$GLOBALS['uc_exec_options'] = array();
$GLOBALS['uc_exec_transients'] = array();
$GLOBALS['uc_exec_scheduled_events'] = array();
$GLOBALS['uc_exec_actions'] = array();
$GLOBALS['uc_exec_as_actions'] = array();

function __($text, $domain = null): string { return (string) $text; }
function apply_filters($hook, $value) { return $value; }
function add_action($hook, $callback, $priority = 10, $acceptedArgs = 1): bool { return true; }
function get_option($name, $default = false) { return array_key_exists($name, $GLOBALS['uc_exec_options']) ? $GLOBALS['uc_exec_options'][$name] : $default; }
function add_option($name, $value = '', $deprecated = '', $autoload = 'yes'): bool {
    if (array_key_exists($name, $GLOBALS['uc_exec_options'])) return false;
    $GLOBALS['uc_exec_options'][$name] = $value;
    return true;
}
function update_option($name, $value, $autoload = null): bool { $GLOBALS['uc_exec_options'][$name] = $value; return true; }
function delete_option($name): bool {
    if (!array_key_exists($name, $GLOBALS['uc_exec_options'])) return false;
    unset($GLOBALS['uc_exec_options'][$name]);
    return true;
}
function wp_schedule_single_event($timestamp, $hook, $args = array()) { $GLOBALS['uc_exec_scheduled_events'][] = array($timestamp, $hook, $args); return true; }
function get_transient($name) { return $GLOBALS['uc_exec_transients'][$name]['value'] ?? false; }
function set_transient($name, $value, $expiration): bool { $GLOBALS['uc_exec_transients'][$name] = array('value' => $value, 'expiration' => $expiration); return true; }
function as_has_scheduled_action($hook, $args = array(), $group = '') {
    foreach ($GLOBALS['uc_exec_as_actions'] as $action) {
        if ($action['hook'] === $hook && $action['args'] === $args && $action['group'] === $group) return true;
    }
    return false;
}
function as_schedule_single_action($timestamp, $hook, $args = array(), $group = '', $unique = false) {
    $id = count($GLOBALS['uc_exec_as_actions']) + 1;
    $GLOBALS['uc_exec_as_actions'][] = compact('id', 'timestamp', 'hook', 'args', 'group', 'unique');
    return $id;
}

require_once __DIR__ . '/../packages/ultimate-commerce-for-woocommerce/src/Contracts/LockStore.php';
require_once __DIR__ . '/../packages/ultimate-commerce-for-woocommerce/src/Contracts/IdempotencyStore.php';
require_once __DIR__ . '/../packages/ultimate-commerce-for-woocommerce/src/Contracts/RateLimiter.php';
require_once __DIR__ . '/../packages/ultimate-commerce-for-woocommerce/src/Concurrency/OptionLockStore.php';
require_once __DIR__ . '/../packages/ultimate-commerce-for-woocommerce/src/Concurrency/Lock.php';
require_once __DIR__ . '/../packages/ultimate-commerce-for-woocommerce/src/Idempotency/OptionIdempotencyStore.php';
require_once __DIR__ . '/../packages/ultimate-commerce-for-woocommerce/src/Idempotency/Idempotency.php';
require_once __DIR__ . '/../packages/ultimate-commerce-for-woocommerce/src/RateLimit/TransientRateLimiter.php';
require_once __DIR__ . '/../packages/ultimate-commerce-for-woocommerce/src/RateLimit/RateLimit.php';
require_once __DIR__ . '/../packages/ultimate-commerce-for-woocommerce/src/Jobs/RetryPolicy.php';
require_once __DIR__ . '/../packages/ultimate-commerce-for-woocommerce/src/Jobs/ActionScheduler.php';

use BadOtter\UltimateCommerce\Concurrency\OptionLockStore;
use BadOtter\UltimateCommerce\Idempotency\OptionIdempotencyStore;
use BadOtter\UltimateCommerce\Jobs\ActionScheduler;
use BadOtter\UltimateCommerce\Jobs\RetryPolicy;
use BadOtter\UltimateCommerce\RateLimit\TransientRateLimiter;

function uc_exec_assert(bool $condition, string $message): void
{
    if ($condition) return;
    fwrite(STDERR, "FAIL: {$message}\n");
    exit(1);
}

$locks = new OptionLockStore();
$lease = $locks->acquire('inventory_adjustment', 'variation:42', 60);
uc_exec_assert(is_string($lease) && strlen($lease) === 32, 'first lock should acquire random lease');
uc_exec_assert($locks->acquire('inventory_adjustment', 'variation:42', 60) === false, 'duplicate lock should fail while lease active');
uc_exec_assert(!$locks->release('inventory_adjustment', 'variation:42', str_repeat('0', 32)), 'wrong lock lease must not release');
uc_exec_assert($locks->release('inventory_adjustment', 'variation:42', $lease), 'matching lock lease should release');
uc_exec_assert(is_string($locks->acquire('inventory_adjustment', 'variation:42', 60)), 'released lock should be acquirable again');

$idempotency = new OptionIdempotencyStore();
$claim = $idempotency->claim('stock_receipt', 'request-abc', 600);
uc_exec_assert(is_array($claim) && $claim['status'] === OptionIdempotencyStore::STATUS_CLAIMED && strlen($claim['lease']) === 32, 'new idempotency key should be claimed');
$duplicate = $idempotency->claim('stock_receipt', 'request-abc', 600);
uc_exec_assert(is_array($duplicate) && $duplicate['status'] === OptionIdempotencyStore::STATUS_IN_PROGRESS && $duplicate['lease'] === '', 'duplicate active operation should report in progress without exposing lease');
uc_exec_assert(!$idempotency->complete('stock_receipt', 'request-abc', str_repeat('0', 32), 'movement:7'), 'wrong idempotency lease must not complete');
uc_exec_assert($idempotency->complete('stock_receipt', 'request-abc', $claim['lease'], 'movement:7'), 'matching idempotency lease should complete');
$completed = $idempotency->claim('stock_receipt', 'request-abc', 600);
uc_exec_assert(is_array($completed) && $completed['status'] === OptionIdempotencyStore::STATUS_COMPLETED && $completed['result_ref'] === 'movement:7', 'completed operation should remain deduplicated with opaque result reference');
uc_exec_assert(!$idempotency->release('stock_receipt', 'request-abc', $claim['lease']), 'completed idempotency record must not be releasable');
$failed = $idempotency->claim('stock_receipt', 'request-retry', 600);
uc_exec_assert(is_array($failed) && $idempotency->release('stock_receipt', 'request-retry', $failed['lease']), 'failed pre-side-effect operation should release matching in-progress claim');
uc_exec_assert(($idempotency->claim('stock_receipt', 'request-retry', 600)['status'] ?? '') === OptionIdempotencyStore::STATUS_CLAIMED, 'released failed operation should be claimable again');

$limiter = new TransientRateLimiter();
$first = $limiter->hit('gift_card_balance', 'opaque-subject-42', 2, 60);
$second = $limiter->hit('gift_card_balance', 'opaque-subject-42', 2, 60);
$third = $limiter->hit('gift_card_balance', 'opaque-subject-42', 2, 60);
uc_exec_assert(is_array($first) && $first['allowed'] && $first['remaining'] === 1, 'first rate-limit hit should be allowed');
uc_exec_assert(is_array($second) && $second['allowed'] && $second['remaining'] === 0, 'second rate-limit hit should consume allowance');
uc_exec_assert(is_array($third) && !$third['allowed'] && $third['retry_after'] > 0, 'third rate-limit hit should be blocked');
$transientNames = implode(' ', array_keys($GLOBALS['uc_exec_transients']));
uc_exec_assert(!str_contains($transientNames, 'opaque-subject-42'), 'raw rate-limit subject must not appear in transient key');

$policy = new RetryPolicy(5, 60, 300);
uc_exec_assert($policy->canRetry(1), 'first failed attempt should be retryable');
uc_exec_assert(!$policy->canRetry(5), 'maximum failed attempt should not retry');
uc_exec_assert($policy->delayAfterFailure(1) === 60, 'first retry delay mismatch');
uc_exec_assert($policy->delayAfterFailure(2) === 120, 'second retry delay mismatch');
uc_exec_assert($policy->delayAfterFailure(4) === 300, 'retry delay should respect cap');

uc_exec_assert(ActionScheduler::schedule('inventory.reconcile', array('variation_id' => 42, 'location_id' => 7)) === true, 'valid Action Scheduler job should schedule');
uc_exec_assert(count($GLOBALS['uc_exec_as_actions']) === 1, 'one background job should be scheduled');
$scheduled = $GLOBALS['uc_exec_as_actions'][0];
uc_exec_assert($scheduled['hook'] === 'ulticofo_job_inventory.reconcile' && $scheduled['group'] === ActionScheduler::GROUP, 'UC job hook/group mismatch');
uc_exec_assert(ActionScheduler::schedule('inventory.reconcile', array('variation_id' => 42, 'location_id' => 7)) === true, 'unique duplicate should be treated as already scheduled');
uc_exec_assert(count($GLOBALS['uc_exec_as_actions']) === 1, 'unique duplicate must not schedule second action');
$badArgs = ActionScheduler::schedule('notify.customer', array('email' => 'customer@example.com'));
uc_exec_assert($badArgs instanceof WP_Error && $badArgs->code === 'uc_job_args_sensitive', 'job personal-data snapshot key should be rejected');
$nestedArgs = ActionScheduler::schedule('inventory.batch', array('ids' => array(1, 2, 3)));
uc_exec_assert($nestedArgs instanceof WP_Error && $nestedArgs->code === 'uc_job_args_invalid', 'nested job payload should be rejected');
uc_exec_assert(ActionScheduler::scheduleRetry('inventory.reconcile', array('variation_id' => 42), 1, $policy) === true, 'retry should schedule through bounded policy');
$retry = $GLOBALS['uc_exec_as_actions'][1];
uc_exec_assert(($retry['args']['ulticofo_attempt'] ?? 0) === 2, 'retry job should carry incremented attempt counter');
$exhausted = ActionScheduler::scheduleRetry('inventory.reconcile', array('variation_id' => 42), 5, $policy);
uc_exec_assert($exhausted instanceof WP_Error && $exhausted->code === 'uc_job_retry_exhausted', 'exhausted retry should fail closed');

fwrite(STDOUT, "Execution-control foundation tests passed\n");
