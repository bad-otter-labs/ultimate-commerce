<?php

namespace BadOtter\UltimateCommerce\Privacy;

defined('ABSPATH') || exit;

final class DataClassification
{
    public const CONTRACT_VERSION = '1.0.0';
    public const PUBLIC_DATA = 'public';
    public const MERCHANT_OPERATIONAL = 'merchant_operational';
    public const PERSONAL = 'personal';
    public const CREDENTIAL_SECRET = 'credential_secret';

    /** @return array<int, string> */
    public static function all(): array
    {
        return array(
            self::PUBLIC_DATA,
            self::MERCHANT_OPERATIONAL,
            self::PERSONAL,
            self::CREDENTIAL_SECRET,
        );
    }

    public static function valid(string $classification): bool
    {
        return in_array($classification, self::all(), true);
    }
}
