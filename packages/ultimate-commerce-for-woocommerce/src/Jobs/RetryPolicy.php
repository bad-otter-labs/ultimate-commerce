<?php

namespace BadOtter\UltimateCommerce\Jobs;

defined('ABSPATH') || exit;

final class RetryPolicy
{
    public function __construct(
        private int $maxAttempts = 5,
        private int $baseDelaySeconds = 60,
        private int $maxDelaySeconds = 3600
    ) {
        if ($maxAttempts < 1 || $maxAttempts > 10) {
            throw new \InvalidArgumentException('Retry attempts must be between 1 and 10.');
        }
        if ($baseDelaySeconds < 5 || $baseDelaySeconds > 3600) {
            throw new \InvalidArgumentException('Retry base delay must be between 5 and 3600 seconds.');
        }
        if ($maxDelaySeconds < $baseDelaySeconds || $maxDelaySeconds > 86400) {
            throw new \InvalidArgumentException('Retry maximum delay is invalid.');
        }
    }

    public function maxAttempts(): int
    {
        return $this->maxAttempts;
    }

    public function canRetry(int $failedAttempt): bool
    {
        return $failedAttempt >= 1 && $failedAttempt < $this->maxAttempts;
    }

    public function delayAfterFailure(int $failedAttempt): int
    {
        if ($failedAttempt < 1) {
            throw new \InvalidArgumentException('Failed attempt must be at least 1.');
        }
        $exponent = min(20, $failedAttempt - 1);
        return min($this->maxDelaySeconds, $this->baseDelaySeconds * (2 ** $exponent));
    }
}
