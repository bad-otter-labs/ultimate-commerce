<?php

namespace BadOtter\UltimateCommerce\Audit;

use BadOtter\UltimateCommerce\Contracts\AuditSink;

defined('ABSPATH') || exit;

final class Audit
{
    private static ?AuditSink $defaultSink = null;

    public static function sink(): AuditSink
    {
        if (!self::$defaultSink) {
            self::$defaultSink = new HookAuditSink();
        }

        $sink = apply_filters('uc_audit_sink', self::$defaultSink);
        return $sink instanceof AuditSink ? $sink : self::$defaultSink;
    }

    /** @return true|\WP_Error */
    public static function record(AuditEvent $event)
    {
        return self::sink()->record($event);
    }
}
