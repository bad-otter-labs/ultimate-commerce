<?php

namespace BadOtter\UltimateCommerce\Compatibility;

use BadOtter\UltimateCommerce\Contracts\Module;
use BadOtter\UltimateCommerce\Support\ModuleRegistry;
use InvalidArgumentException;

defined('ABSPATH') || exit;

/**
 * Read-only, opt-in bridge to Labs Superpowers v0 discovery.
 *
 * This is NOT a second UC module registry or an authorization provider. Existing
 * WooCommerce and Ultimate Commerce registration, settings, stock and licences
 * remain authoritative. No network requests or writes occur here.
 */
final class LabsModuleMap
{
    public const SCHEMA = 'labs.superpowers.module.v0';

    /**
     * Export registered UC modules using explicitly supplied product mappings.
     * The caller, never an untrusted request, owns product and entitlement IDs.
     *
     * @param array<string, array{product_id:string,display_name:string,version:string,entitlement_id:?string,requires:list<string>}> $products
     * @return list<array<string, mixed>>
     */
    public static function declarations(ModuleRegistry $registry, array $products): array
    {
        $modules = $registry->all();
        $active = array();
        foreach ($modules as $module) {
            if (isset($products[$module->product()])) {
                $active[$module->product()] = true;
            }
        }

        $manifests = array();
        foreach ($products as $legacyProduct => $spec) {
            if (!isset($active[$legacyProduct])) {
                continue;
            }
            self::validateProduct($spec);
            $coreId = $spec['product_id'] . '/core';
            $manifests[$coreId] = self::manifest(
                $coreId,
                $spec['product_id'],
                $spec['display_name'],
                $spec['version'],
                $spec['entitlement_id'],
                $spec['requires'],
                'core'
            );
        }

        $identities = array();
        foreach ($modules as $key => $module) {
            if (!isset($active[$module->product()])) {
                continue;
            }
            if (preg_match('/^[a-z0-9_-]+$/D', $key) !== 1) {
                throw new InvalidArgumentException('Invalid UC module key for Labs discovery');
            }
            $id = $products[$module->product()]['product_id'] . '/' . str_replace('_', '-', $key);
            if (isset($manifests[$id]) || in_array($id, $identities, true)) {
                throw new InvalidArgumentException('Ambiguous Labs module identity');
            }
            $identities[$key] = $id;
        }

        foreach ($modules as $key => $module) {
            if (!isset($active[$module->product()])) {
                continue;
            }
            $spec = $products[$module->product()];
            $requires = array($spec['product_id'] . '/core');
            foreach ($module->dependencies() as $dependency) {
                if (!isset($identities[$dependency])) {
                    throw new InvalidArgumentException('Unmapped required UC module dependency');
                }
                $requires[] = $identities[$dependency];
            }
            $id = $identities[$key];
            $manifests[$id] = self::manifest(
                $id,
                $spec['product_id'],
                $module->name(),
                $spec['version'],
                $spec['entitlement_id'],
                array_values(array_unique($requires)),
                $module->key()
            );
        }

        ksort($manifests, SORT_STRING);
        return array_values($manifests);
    }

    /**
     * Explicitly register discovery metadata only when the verified optional
     * foundation library is already loaded by the host. Never loads it for UC.
     *
     * @param object $foundationRegistry
     * @param list<array<string, mixed>> $manifests
     */
    public static function registerInto($foundationRegistry, array $manifests): bool
    {
        if (!is_object($foundationRegistry) || !is_a($foundationRegistry, 'BadOtterLabs\\SuperpowersV0\\ModuleRegistry')) {
            return false;
        }

        foreach ($manifests as $manifest) {
            call_user_func(array($foundationRegistry, 'register'), $manifest);
        }
        return true;
    }

    /** @param array<string, mixed> $spec */
    private static function validateProduct(array $spec): void
    {
        $id = $spec['product_id'] ?? null;
        $name = $spec['display_name'] ?? null;
        $version = $spec['version'] ?? null;
        $entitlement = $spec['entitlement_id'] ?? null;
        $requires = $spec['requires'] ?? null;
        if (!is_string($id) || preg_match('/^[a-z][a-z0-9-]*$/D', $id) !== 1 ||
            !is_string($name) || $name === '' || strlen($name) > 120 ||
            !is_string($version) || preg_match('/^\\d+\\.\\d+\\.\\d+([-.][0-9A-Za-z.-]+)?$/D', $version) !== 1 ||
            ($entitlement !== null && (!is_string($entitlement) ||
                preg_match('/^[a-z][a-z0-9-]*(\\/[a-z][a-z0-9-]*)?$/D', $entitlement) !== 1)) ||
            !is_array($requires) || !array_is_list($requires) ||
            count($requires) !== count(array_unique($requires))) {
            throw new InvalidArgumentException('Invalid trusted Labs product mapping');
        }
        foreach ($requires as $requirement) {
            if (!is_string($requirement) ||
                preg_match('/^[a-z][a-z0-9-]*\\/[a-z][a-z0-9-]*$/D', $requirement) !== 1 ||
                $requirement === $id . '/core') {
                throw new InvalidArgumentException('Invalid Labs core dependency');
            }
        }
    }

    /**
     * @param list<string> $requires
     * @return array<string, mixed>
     */
    private static function manifest(
        string $moduleId,
        string $productId,
        string $name,
        string $version,
        ?string $entitlement,
        array $requires,
        string $legacyKey
    ): array {
        return array(
            'schema' => self::SCHEMA,
            'module_id' => $moduleId,
            'product_id' => $productId,
            'display_name' => $name,
            'version' => $version,
            'runtime' => 'wordpress-plugin',
            // No permission, stock, or financial authority is conferred by discovery.
            'capabilities' => array(),
            'requires' => $requires,
            'optional_integrations' => array(),
            'authorities' => array(),
            'entitlement_id' => $entitlement,
            'legacy_module_id' => $legacyKey,
        );
    }
}
