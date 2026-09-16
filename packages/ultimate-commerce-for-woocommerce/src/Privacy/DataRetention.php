<?php

namespace BadOtter\UltimateCommerce\Privacy;

defined('ABSPATH') || exit;

final class DataRetention
{
    public const TRANSIENT = 'transient';
    public const ACCOUNT_LIFETIME = 'account_lifetime';
    public const MERCHANT_POLICY = 'merchant_policy';
    public const OPERATIONAL_HISTORY = 'operational_history';
    public const LEGAL_POLICY = 'legal_policy';

    /** @return array<int, string> */
    public static function all(): array
    {
        return array(
            self::TRANSIENT,
            self::ACCOUNT_LIFETIME,
            self::MERCHANT_POLICY,
            self::OPERATIONAL_HISTORY,
            self::LEGAL_POLICY,
        );
    }

    public static function valid(string $retention): bool
    {
        return in_array($retention, self::all(), true);
    }
}
