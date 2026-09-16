<?php

namespace BadOtter\UltimateCommerce\Contracts;

defined('ABSPATH') || exit;

interface Module
{
    public const CONTRACT_VERSION = '1.0.0';
    public const TIER_FREE = 'free';
    public const TIER_PRO = 'pro';
    public const TIER_EXTENSION = 'extension';

    public function key(): string;

    public function name(): string;

    public function product(): string;

    public function tier(): string;

    /** @return list<string> */
    public function dependencies(): array;

    /** @return array<string, string> */
    public function compatibility(): array;

    /** @return array<string, mixed> */
    public function settingsSchema(): array;

    /** @return array<string, list<string>> */
    public function assets(): array;

    public function register(): void;
}
