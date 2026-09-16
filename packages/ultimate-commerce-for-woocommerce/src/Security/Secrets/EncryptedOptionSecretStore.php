<?php

namespace BadOtter\UltimateCommerce\Security\Secrets;

use BadOtter\UltimateCommerce\Contracts\SecretStore;

defined('ABSPATH') || exit;

final class EncryptedOptionSecretStore implements SecretStore
{
    private const OPTION = 'uc_secret_store_v1';
    private const VERSION = 1;

    /** @return string|\WP_Error|null */
    public function get(string $key)
    {
        if (!self::validKey($key)) {
            return self::error('uc_secret_key_invalid', 'Secret key is invalid.');
        }

        $values = get_option(self::OPTION, array());
        if (!is_array($values) || !array_key_exists($key, $values)) {
            return null;
        }
        if (!is_string($values[$key]) || $values[$key] === '') {
            return self::error('uc_secret_corrupt', 'Stored secret data is invalid.');
        }

        return $this->decrypt($key, $values[$key]);
    }

    /** @return true|\WP_Error */
    public function put(string $key, string $value)
    {
        if (!self::validKey($key)) {
            return self::error('uc_secret_key_invalid', 'Secret key is invalid.');
        }
        if ($value === '') {
            return self::error('uc_secret_value_invalid', 'Secret value must not be empty.');
        }

        $encrypted = $this->encrypt($key, $value);
        if ($encrypted instanceof \WP_Error) {
            return $encrypted;
        }

        $values = get_option(self::OPTION, array());
        if (!is_array($values)) {
            $values = array();
        }
        $values[$key] = $encrypted;

        if (!update_option(self::OPTION, $values, false)) {
            $stored = get_option(self::OPTION, array());
            if (!is_array($stored) || ($stored[$key] ?? null) !== $encrypted) {
                return self::error('uc_secret_store_write_failed', 'The secret could not be stored.');
            }
        }

        return true;
    }

    public function delete(string $key): bool
    {
        if (!self::validKey($key)) {
            return false;
        }

        $values = get_option(self::OPTION, array());
        if (!is_array($values) || !array_key_exists($key, $values)) {
            return true;
        }

        unset($values[$key]);
        if ($values === array()) {
            return delete_option(self::OPTION);
        }

        return update_option(self::OPTION, $values, false);
    }

    /** @return string|\WP_Error */
    private function encrypt(string $key, string $value)
    {
        $material = $this->keyMaterial();
        if ($material instanceof \WP_Error) {
            return $material;
        }

        $aad = 'uc-secret-store:v1:' . $key;
        try {
            if (function_exists('sodium_crypto_aead_xchacha20poly1305_ietf_encrypt')) {
                $nonce = random_bytes(SODIUM_CRYPTO_AEAD_XCHACHA20POLY1305_IETF_NPUBBYTES);
                $ciphertext = sodium_crypto_aead_xchacha20poly1305_ietf_encrypt($value, $aad, $nonce, $material);
                return $this->encodeEnvelope('xchacha20poly1305', $nonce, $ciphertext);
            }

            if (function_exists('openssl_encrypt')) {
                $nonce = random_bytes(12);
                $tag = '';
                $ciphertext = openssl_encrypt($value, 'aes-256-gcm', $material, OPENSSL_RAW_DATA, $nonce, $tag, $aad, 16);
                if (!is_string($ciphertext) || strlen($tag) !== 16) {
                    return self::error('uc_secret_crypto_failed', 'Secret encryption failed.');
                }
                return $this->encodeEnvelope('aes-256-gcm', $nonce, $tag . $ciphertext);
            }
        } catch (\Throwable $exception) {
            return self::error('uc_secret_crypto_failed', 'Secret encryption failed.');
        }

        return self::error('uc_secret_crypto_unavailable', 'No supported secret encryption backend is available.');
    }

    /** @return string|\WP_Error */
    private function decrypt(string $key, string $stored)
    {
        $decoded = base64_decode($stored, true);
        $envelope = is_string($decoded) ? json_decode($decoded, true) : null;
        if (!is_array($envelope) || (int) ($envelope['v'] ?? 0) !== self::VERSION) {
            return self::error('uc_secret_corrupt', 'Stored secret data is invalid.');
        }

        $algorithm = (string) ($envelope['alg'] ?? '');
        $nonce = base64_decode((string) ($envelope['nonce'] ?? ''), true);
        $ciphertext = base64_decode((string) ($envelope['ciphertext'] ?? ''), true);
        if (!is_string($nonce) || !is_string($ciphertext)) {
            return self::error('uc_secret_corrupt', 'Stored secret data is invalid.');
        }

        $material = $this->keyMaterial();
        if ($material instanceof \WP_Error) {
            return $material;
        }
        $aad = 'uc-secret-store:v1:' . $key;

        try {
            if ($algorithm === 'xchacha20poly1305' && function_exists('sodium_crypto_aead_xchacha20poly1305_ietf_decrypt')) {
                if (strlen($nonce) !== SODIUM_CRYPTO_AEAD_XCHACHA20POLY1305_IETF_NPUBBYTES) {
                    return self::error('uc_secret_corrupt', 'Stored secret nonce is invalid.');
                }
                $plain = sodium_crypto_aead_xchacha20poly1305_ietf_decrypt($ciphertext, $aad, $nonce, $material);
                return is_string($plain) ? $plain : self::error('uc_secret_decrypt_failed', 'The secret could not be decrypted.');
            }

            if ($algorithm === 'aes-256-gcm' && function_exists('openssl_decrypt')) {
                if (strlen($nonce) !== 12 || strlen($ciphertext) < 17) {
                    return self::error('uc_secret_corrupt', 'Stored secret data is invalid.');
                }
                $tag = substr($ciphertext, 0, 16);
                $cipher = substr($ciphertext, 16);
                $plain = openssl_decrypt($cipher, 'aes-256-gcm', $material, OPENSSL_RAW_DATA, $nonce, $tag, $aad);
                return is_string($plain) ? $plain : self::error('uc_secret_decrypt_failed', 'The secret could not be decrypted.');
            }
        } catch (\Throwable $exception) {
            return self::error('uc_secret_decrypt_failed', 'The secret could not be decrypted.');
        }

        return self::error('uc_secret_crypto_unavailable', 'The encryption backend for this secret is unavailable.');
    }

    /** @return string|\WP_Error */
    private function keyMaterial()
    {
        $salt = (string) wp_salt('secure_auth');
        if ($salt === '') {
            return self::error('uc_secret_key_unavailable', 'WordPress secret key material is unavailable.');
        }
        return hash_hkdf('sha256', $salt, 32, 'ultimate-commerce-secret-store-v1');
    }

    /** @return string|\WP_Error */
    private function encodeEnvelope(string $algorithm, string $nonce, string $ciphertext)
    {
        $json = wp_json_encode(array(
            'v' => self::VERSION,
            'alg' => $algorithm,
            'nonce' => base64_encode($nonce),
            'ciphertext' => base64_encode($ciphertext),
        ));
        if (!is_string($json)) {
            return self::error('uc_secret_crypto_failed', 'Secret encryption metadata could not be encoded.');
        }
        return base64_encode($json);
    }

    private static function validKey(string $key): bool
    {
        return (bool) preg_match('/^[a-z][a-z0-9_.:-]{0,99}$/', $key);
    }

    private static function error(string $code, string $message): \WP_Error
    {
        return new \WP_Error($code, __($message, 'ultimate-commerce-for-woocommerce'));
    }
}
