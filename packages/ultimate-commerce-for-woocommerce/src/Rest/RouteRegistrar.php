<?php

namespace BadOtter\UltimateCommerce\Rest;

defined('ABSPATH') || exit;

final class RouteRegistrar
{
    public const NAMESPACE = 'ultimate-commerce/v1';

    private const ARG_TYPES = array(
        'string',
        'integer',
        'number',
        'boolean',
        'array',
        'object',
    );

    /**
     * Register a UC-owned REST route with an explicit permission callback and typed args.
     * Call from rest_api_init.
     *
     * @param array<string, mixed> $definition
     */
    public static function register(string $route, array $definition): bool
    {
        $route = '/' . ltrim(trim($route), '/');
        if ($route === '/' || str_contains($route, '..')) {
            throw new \InvalidArgumentException('Ultimate Commerce REST route is invalid.');
        }

        if (empty($definition['methods'])) {
            throw new \InvalidArgumentException('Ultimate Commerce REST routes require explicit methods.');
        }

        if (!isset($definition['callback']) || !is_callable($definition['callback'])) {
            throw new \InvalidArgumentException('Ultimate Commerce REST routes require a callable callback.');
        }

        if (!isset($definition['permission_callback']) || !is_callable($definition['permission_callback'])) {
            throw new \InvalidArgumentException('Ultimate Commerce REST routes require an explicit permission_callback.');
        }

        $definition['args'] = $definition['args'] ?? array();
        if (!is_array($definition['args'])) {
            throw new \InvalidArgumentException('Ultimate Commerce REST route args must be an array.');
        }

        self::validateArgs($definition['args']);

        return (bool) register_rest_route(self::NAMESPACE, $route, $definition, true);
    }

    /** @return array<string, mixed> */
    public static function pageArg(int $default = 1, int $maximum = 1000): array
    {
        if ($default < 1 || $maximum < 1 || $default > $maximum) {
            throw new \InvalidArgumentException('Invalid page bounds.');
        }

        return array(
            'type' => 'integer',
            'default' => $default,
            'minimum' => 1,
            'maximum' => $maximum,
            'sanitize_callback' => static fn($value): int => (int) $value,
            'validate_callback' => static fn($value): bool => is_numeric($value) && (int) $value >= 1 && (int) $value <= $maximum,
        );
    }

    /** @return array<string, mixed> */
    public static function perPageArg(int $default = 20, int $maximum = 100): array
    {
        if ($default < 1 || $maximum < 1 || $maximum > 100 || $default > $maximum) {
            throw new \InvalidArgumentException('Invalid per-page bounds.');
        }

        return array(
            'type' => 'integer',
            'default' => $default,
            'minimum' => 1,
            'maximum' => $maximum,
            'sanitize_callback' => static fn($value): int => (int) $value,
            'validate_callback' => static fn($value): bool => is_numeric($value) && (int) $value >= 1 && (int) $value <= $maximum,
        );
    }

    /** @param array<string, mixed> $args */
    private static function validateArgs(array $args): void
    {
        foreach ($args as $name => $schema) {
            if (!is_string($name) || sanitize_key($name) !== $name) {
                throw new \InvalidArgumentException('Ultimate Commerce REST argument names must use sanitize_key form.');
            }

            if (!is_array($schema) || empty($schema['type']) || !in_array($schema['type'], self::ARG_TYPES, true)) {
                throw new \InvalidArgumentException('Ultimate Commerce REST arguments require a supported type.');
            }

            if (isset($schema['required']) && !is_bool($schema['required'])) {
                throw new \InvalidArgumentException('Ultimate Commerce REST required flags must be boolean.');
            }

            if (isset($schema['enum']) && (!is_array($schema['enum']) || $schema['enum'] === array())) {
                throw new \InvalidArgumentException('Ultimate Commerce REST enum constraints must be non-empty arrays.');
            }

            foreach (array('sanitize_callback', 'validate_callback') as $callbackKey) {
                if (isset($schema[$callbackKey]) && !is_callable($schema[$callbackKey])) {
                    throw new \InvalidArgumentException('Ultimate Commerce REST callbacks must be callable.');
                }
            }
        }
    }
}
