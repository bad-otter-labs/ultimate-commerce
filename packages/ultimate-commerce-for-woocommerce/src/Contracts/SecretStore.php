<?php

namespace BadOtter\UltimateCommerce\Contracts;

defined('ABSPATH') || exit;

interface SecretStore
{
    /** @return string|\WP_Error|null */
    public function get(string $key);

    /** @return true|\WP_Error */
    public function put(string $key, string $value);

    public function delete(string $key): bool;
}
