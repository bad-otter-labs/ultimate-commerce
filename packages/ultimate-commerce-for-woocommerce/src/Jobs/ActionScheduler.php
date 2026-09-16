<?php

namespace BadOtter\UltimateCommerce\Jobs;

defined('ABSPATH') || exit;

final class ActionScheduler
{
    public const GROUP = 'ultimate-commerce';
    private const MAX_ARGS = 16;
    private const MAX_STRING_ARG = 191;
    private const MAX_SCHEDULE_AHEAD = 31536000;

    /** @param array<string, scalar|null> $args
     *  @return true|\WP_Error
     */
    public static function schedule(string $job, array $args = array(), int $timestamp = 0, bool $unique = true)
    {
        if (!self::validJob($job)) {
            return self::error('uc_job_name_invalid', 'Background job name is invalid.');
        }
        $argsProblem = self::validateArgs($args);
        if ($argsProblem instanceof \WP_Error) {
            return $argsProblem;
        }
        if (!function_exists('as_schedule_single_action')) {
            return self::error('uc_action_scheduler_unavailable', 'Action Scheduler is unavailable.');
        }

        $now = time();
        $timestamp = $timestamp > 0 ? $timestamp : $now;
        if ($timestamp < $now - 60 || $timestamp > $now + self::MAX_SCHEDULE_AHEAD) {
            return self::error('uc_job_schedule_invalid', 'Background job schedule is outside the allowed range.');
        }
        $timestamp = max($now, $timestamp);
        $hook = self::hook($job);

        if ($unique && function_exists('as_has_scheduled_action') && as_has_scheduled_action($hook, $args, self::GROUP)) {
            return true;
        }

        $actionId = as_schedule_single_action($timestamp, $hook, $args, self::GROUP, $unique);
        if (!is_int($actionId) || $actionId <= 0) {
            return self::error('uc_job_schedule_failed', 'Background job could not be scheduled.');
        }

        return true;
    }

    /** @param array<string, scalar|null> $args
     *  @return true|\WP_Error
     */
    public static function scheduleRetry(string $job, array $args, int $failedAttempt, ?RetryPolicy $policy = null)
    {
        $policy = $policy ?? new RetryPolicy();
        if (!$policy->canRetry($failedAttempt)) {
            return self::error('uc_job_retry_exhausted', 'Background job retry limit has been reached.');
        }

        $args['uc_attempt'] = $failedAttempt + 1;
        return self::schedule($job, $args, time() + $policy->delayAfterFailure($failedAttempt), true);
    }

    public static function hook(string $job): string
    {
        return 'uc_job_' . $job;
    }

    private static function validJob(string $job): bool
    {
        return (bool) preg_match('/^[a-z][a-z0-9_.:-]{0,63}$/', $job);
    }

    /** @param array<string, scalar|null> $args
     *  @return true|\WP_Error
     */
    private static function validateArgs(array $args)
    {
        if (count($args) > self::MAX_ARGS) {
            return self::error('uc_job_args_too_large', 'Background job contains too many arguments.');
        }

        foreach ($args as $key => $value) {
            if (!is_string($key) || !preg_match('/^[a-z][a-z0-9_]{0,63}$/', $key)) {
                return self::error('uc_job_args_invalid', 'Background job argument name is invalid.');
            }
            if (self::sensitiveOrPersonalKey($key)) {
                return self::error('uc_job_args_sensitive', 'Background jobs must carry identifiers rather than credentials or personal-data snapshots.');
            }
            if ($value !== null && !is_scalar($value)) {
                return self::error('uc_job_args_invalid', 'Background job arguments must be scalar identifiers or flags.');
            }
            if (is_string($value) && strlen($value) > self::MAX_STRING_ARG) {
                return self::error('uc_job_args_too_large', 'Background job string argument is too large.');
            }
        }

        return true;
    }

    private static function sensitiveOrPersonalKey(string $key): bool
    {
        return (bool) preg_match('/(^|_)(password|secret|token|authorization|cookie|nonce|credential|api_key|licen[cs]e_key|email|phone|address|first_name|last_name)($|_)/', strtolower($key));
    }

    private static function error(string $code, string $message): \WP_Error
    {
        return new \WP_Error($code, __($message, 'ultimate-commerce-for-woocommerce'));
    }
}
