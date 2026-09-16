<?php

declare(strict_types=1);

define('ABSPATH', __DIR__ . '/');
define('ULTIMATE_COMMERCE_VERSION', '0.2.0-test');

final class WP_Error
{
    /** @param array<string, mixed> $data */
    public function __construct(public string $code, public string $message, public array $data = array())
    {
    }
}

$GLOBALS['uc_ext_options'] = array();
$GLOBALS['uc_ext_scheduled'] = array();
$GLOBALS['uc_ext_http'] = array();
$GLOBALS['uc_ext_filters'] = array();

function __($text, $domain = null): string
{
    return (string) $text;
}

function wp_json_encode($value)
{
    return json_encode($value, JSON_UNESCAPED_SLASHES);
}

function wp_salt($scheme = 'auth'): string
{
    return 'uc-test-salt-' . $scheme . '-0123456789abcdefghijklmnopqrstuvwxyz';
}

function get_option($name, $default = false)
{
    return array_key_exists($name, $GLOBALS['uc_ext_options']) ? $GLOBALS['uc_ext_options'][$name] : $default;
}

function update_option($name, $value, $autoload = null): bool
{
    $GLOBALS['uc_ext_options'][$name] = $value;
    return true;
}

function add_option($name, $value = '', $deprecated = '', $autoload = 'yes'): bool
{
    if (array_key_exists($name, $GLOBALS['uc_ext_options'])) {
        return false;
    }
    $GLOBALS['uc_ext_options'][$name] = $value;
    return true;
}

function delete_option($name): bool
{
    if (!array_key_exists($name, $GLOBALS['uc_ext_options'])) {
        return false;
    }
    unset($GLOBALS['uc_ext_options'][$name]);
    return true;
}

function apply_filters($hook, $value)
{
    return $GLOBALS['uc_ext_filters'][$hook] ?? $value;
}

function add_action($hook, $callback, $priority = 10, $acceptedArgs = 1): bool
{
    return true;
}

function wp_schedule_single_event($timestamp, $hook, $args = array())
{
    $GLOBALS['uc_ext_scheduled'][] = array($timestamp, $hook, $args);
    return true;
}

function wp_parse_url($url)
{
    return parse_url($url);
}

function wp_http_validate_url($url)
{
    $parts = parse_url($url);
    if (!is_array($parts) || empty($parts['host'])) {
        return false;
    }
    $host = strtolower((string) $parts['host']);
    if (in_array($host, array('localhost', 'metadata.google.internal'), true)) {
        return false;
    }
    return $url;
}

function wp_safe_remote_request($url, $args = array())
{
    $GLOBALS['uc_ext_http'][] = array('url' => $url, 'args' => $args);
    return array('response' => array('code' => 200), 'body' => '{}');
}

require_once __DIR__ . '/../packages/ultimate-commerce-for-woocommerce/src/Contracts/SecretStore.php';
require_once __DIR__ . '/../packages/ultimate-commerce-for-woocommerce/src/Contracts/ReplayStore.php';
require_once __DIR__ . '/../packages/ultimate-commerce-for-woocommerce/src/Security/GuestToken.php';
require_once __DIR__ . '/../packages/ultimate-commerce-for-woocommerce/src/Security/Secrets/EncryptedOptionSecretStore.php';
require_once __DIR__ . '/../packages/ultimate-commerce-for-woocommerce/src/Security/SecretStore.php';
require_once __DIR__ . '/../packages/ultimate-commerce-for-woocommerce/src/Http/ProviderHttp.php';
require_once __DIR__ . '/../packages/ultimate-commerce-for-woocommerce/src/Security/Replay/OptionReplayStore.php';
require_once __DIR__ . '/../packages/ultimate-commerce-for-woocommerce/src/Security/SignedWebhook.php';

use BadOtter\UltimateCommerce\Http\ProviderHttp;
use BadOtter\UltimateCommerce\Security\GuestToken;
use BadOtter\UltimateCommerce\Security\Replay\OptionReplayStore;
use BadOtter\UltimateCommerce\Security\SecretStore;
use BadOtter\UltimateCommerce\Security\Secrets\EncryptedOptionSecretStore;
use BadOtter\UltimateCommerce\Security\SignedWebhook;

function uc_external_assert(bool $condition, string $message): void
{
    if ($condition) {
        return;
    }
    fwrite(STDERR, "FAIL: {$message}\n");
    exit(1);
}

$token = GuestToken::issue('return_status', 'return_42', 120, array('channel' => 'email'));
uc_external_assert(is_string($token), 'guest token should be issued');
$verifiedToken = GuestToken::verify($token, 'return_status', 'return_42');
uc_external_assert(is_array($verifiedToken), 'guest token should verify');
uc_external_assert(($verifiedToken['claims']['channel'] ?? '') === 'email', 'guest token claims should survive verification');
$wrongPurpose = GuestToken::verify($token, 'stock_alert', 'return_42');
uc_external_assert($wrongPurpose instanceof WP_Error && $wrongPurpose->code === 'uc_guest_token_invalid', 'purpose mismatch must fail signature verification');
$wrongSubject = GuestToken::verify($token, 'return_status', 'return_43');
uc_external_assert($wrongSubject instanceof WP_Error && $wrongSubject->code === 'uc_guest_token_subject', 'subject mismatch must fail');
$parts = explode('.', $token);
$parts[1] = substr($parts[1], 0, -1) . ($parts[1][-1] === 'A' ? 'B' : 'A');
$tampered = GuestToken::verify(implode('.', $parts), 'return_status', 'return_42');
uc_external_assert($tampered instanceof WP_Error, 'tampered guest token must fail');

$secretStore = new EncryptedOptionSecretStore();
$stored = $secretStore->put('provider.api_key', 'super-secret-value');
uc_external_assert($stored === true, 'encrypted secret should store');
$rawStore = json_encode($GLOBALS['uc_ext_options']['uc_secret_store_v1'] ?? array());
uc_external_assert(is_string($rawStore) && !str_contains($rawStore, 'super-secret-value'), 'secret must not be stored in plaintext');
uc_external_assert($secretStore->get('provider.api_key') === 'super-secret-value', 'encrypted secret should decrypt');
uc_external_assert(SecretStore::put('provider.second_key', 'second-secret') === true, 'secret facade should use default provider');
uc_external_assert(SecretStore::get('provider.second_key') === 'second-secret', 'secret facade should read through provider');
uc_external_assert($secretStore->delete('provider.api_key'), 'secret delete should succeed');
uc_external_assert($secretStore->get('provider.api_key') === null, 'deleted secret should be absent');

$response = ProviderHttp::request('GET', 'https://api.example.com/v1/orders', array('api.example.com'), array(
    'timeout' => 99,
    'redirection' => 9,
    'sslverify' => false,
    'headers' => array('Host' => 'internal.invalid', 'Authorization' => 'Bearer test'),
));
uc_external_assert(is_array($response), 'allowlisted provider request should run');
$http = $GLOBALS['uc_ext_http'][0]['args'];
uc_external_assert($http['timeout'] === 20, 'provider timeout should be bounded');
uc_external_assert($http['redirection'] === 0, 'provider redirects should be disabled');
uc_external_assert($http['sslverify'] === true && $http['reject_unsafe_urls'] === true, 'provider TLS and unsafe URL checks must be forced');
uc_external_assert(!isset($http['headers']['Host']), 'caller Host override must be removed');
uc_external_assert(isset($http['headers']['Authorization']), 'provider auth header should remain available');
uc_external_assert(ProviderHttp::request('GET', 'http://api.example.com/v1', array('api.example.com')) instanceof WP_Error, 'HTTP provider URL must fail');
uc_external_assert(ProviderHttp::request('GET', 'https://evil.example/v1', array('api.example.com')) instanceof WP_Error, 'non-allowlisted provider host must fail');
uc_external_assert(ProviderHttp::request('GET', 'https://127.0.0.1/v1', array('127.0.0.1')) instanceof WP_Error, 'literal IP provider URL must fail');
uc_external_assert(ProviderHttp::request('GET', 'https://localhost/v1', array('localhost')) instanceof WP_Error, 'WordPress-unsafe provider URL must fail');

$replay = new OptionReplayStore();
$lease = $replay->claim('shipping_provider', 'evt_123', 600);
uc_external_assert(is_string($lease) && strlen($lease) === 32, 'first replay claim should return a lease');
uc_external_assert($replay->claim('shipping_provider', 'evt_123', 600) === false, 'duplicate replay claim should fail');
uc_external_assert(!$replay->release('shipping_provider', 'evt_123', str_repeat('0', 32)), 'wrong replay lease must not release claim');
uc_external_assert($replay->release('shipping_provider', 'evt_123', $lease), 'matching replay lease should release claim');
uc_external_assert(is_string($replay->claim('shipping_provider', 'evt_123', 600)), 'released claim should be claimable again');

$webhookReplay = new OptionReplayStore();
$timestamp = (string) time();
$eventId = 'evt_webhook_1';
$body = '{"order":42}';
$webhookSecret = 'test-webhook-secret-0123456789';
$signature = 'v1=' . hash_hmac('sha256', $timestamp . '.' . $eventId . '.' . $body, $webhookSecret);
$accepted = SignedWebhook::verifyAndClaim('carrier', $eventId, $timestamp, $signature, $body, $webhookSecret, $webhookReplay, 300, 600);
uc_external_assert(is_array($accepted) && !empty($accepted['lease']), 'valid signed webhook should verify and claim replay lease');
$duplicate = SignedWebhook::verifyAndClaim('carrier', $eventId, $timestamp, $signature, $body, $webhookSecret, $webhookReplay, 300, 600);
uc_external_assert($duplicate instanceof WP_Error && $duplicate->code === 'uc_webhook_replay', 'duplicate webhook event must be rejected');
uc_external_assert(SignedWebhook::release($webhookReplay, 'carrier', $eventId, (string) $accepted['lease']), 'failed handler should be able to release its matching lease');
$badSignature = SignedWebhook::verify('carrier', 'evt_webhook_2', $timestamp, 'v1=' . str_repeat('0', 64), $body, $webhookSecret);
uc_external_assert($badSignature instanceof WP_Error && $badSignature->code === 'uc_webhook_signature', 'bad webhook signature must fail');
$stale = SignedWebhook::verify('carrier', 'evt_webhook_3', (string) (time() - 1000), $signature, $body, $webhookSecret, 300);
uc_external_assert($stale instanceof WP_Error && $stale->code === 'uc_webhook_timestamp', 'stale webhook timestamp must fail');

fwrite(STDOUT, "External-boundary security tests passed\n");
