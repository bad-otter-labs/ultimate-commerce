<?php

namespace BadOtter\UltimateCommerce\Contracts;

defined('ABSPATH') || exit;

interface Module
{
    public function key(): string;

    public function register(): void;
}
