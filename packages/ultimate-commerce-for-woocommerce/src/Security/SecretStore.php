<?php

namespace BadOtter\UltimateCommerce\Security;

use BadOtter\UltimateCommerce\Contracts\SecretStore as SecretStoreContract;
use BadOtter\UltimateCommerce\Security\Secrets\EncryptedOptionSecretStore;

defined('ABSPATH') || exit;

final class SecretStore
{
    private static ?SecretStoreContract $defaultProvider = null;

    public static function provider(): SecretStoreContract
    {
        if (!self::$defaultProvider) {
            self::$defaultProvider = new EncryptedOptionSecretStore();
        }

        $provider = apply_filters('uc_secret_store_provider', self::$defaultProvider);
        return $provider instanceof SecretStoreContract ? $provider : self::$defaultProvider;
    }

    /** @return string|\WP_Error|null */
    public static function get(string $key)
    {
        return self::provider()->get($key);
    }

    /** @return true|\WP_Error */
    public static function put(string $key, string $value)
    {
        return self::provider()->put($key, $value);
    }

    public static function delete(string $key): bool
    {
        return self::provider()->delete($key);
    }
}
