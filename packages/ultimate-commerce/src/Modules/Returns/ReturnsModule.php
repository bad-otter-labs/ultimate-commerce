<?php

namespace BadOtter\UltimateCommerce\Modules\Returns;

use BadOtter\UltimateCommerce\Contracts\Module;

defined('ABSPATH') || exit;

final class ReturnsModule implements Module
{
    public function key(): string
    {
        return 'returns';
    }

    public function register(): void
    {
        add_filter('uc_return_eligibility', array($this, 'defaultEligibility'), 1, 3);
        do_action('uc_returns_ready', $this);
    }

    public function defaultEligibility($eligibility, $order = null, $item = null): array
    {
        if (is_array($eligibility) && array_key_exists('eligible', $eligibility)) {
            return $eligibility;
        }

        return array(
            'eligible' => false,
            'reason' => 'no_policy_provider',
        );
    }
}
