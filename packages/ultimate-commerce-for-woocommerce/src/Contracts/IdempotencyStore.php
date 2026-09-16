<?php

namespace BadOtter\UltimateCommerce\Contracts;

defined('ABSPATH') || exit;

interface IdempotencyStore
{
    /**
     * @return array{status:string,lease:string,result_ref:string}|\WP_Error
     */
    public function claim(string $scope, string $key, int $ttlSeconds = 86400);

    public function complete(string $scope, string $key, string $lease, string $resultRef = ''): bool;

    public function release(string $scope, string $key, string $lease): bool;
}
