<?php

namespace BadOtter\UltimateCommerce\Audit;

use BadOtter\UltimateCommerce\Contracts\AuditSink;

defined('ABSPATH') || exit;

final class HookAuditSink implements AuditSink
{
    public function record(AuditEvent $event)
    {
        do_action('uc_audit_event', $event);
        return true;
    }
}
