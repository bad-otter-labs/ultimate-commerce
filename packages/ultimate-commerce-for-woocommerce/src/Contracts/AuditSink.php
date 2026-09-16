<?php

namespace BadOtter\UltimateCommerce\Contracts;

use BadOtter\UltimateCommerce\Audit\AuditEvent;

defined('ABSPATH') || exit;

interface AuditSink
{
    /** @return true|\WP_Error */
    public function record(AuditEvent $event);
}
