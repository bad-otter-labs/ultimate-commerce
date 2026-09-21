<?php

namespace BadOtter\UltimateCommerce\Support;

use BadOtter\UltimateCommerce\Contracts\Module;

defined('ABSPATH') || exit;

final class ModuleRegistry
{
    /** @var array<string, Module> */
    private array $modules = array();

    /** @var array<string, bool> */
    private array $booted = array();

    /** @var array<string, string> */
    private array $status = array();

    /** @var array<string, string> */
    private array $issues = array();

    public function register(Module $module): bool
    {
        $key = sanitize_key($module->key());
        if ($key === '' || $key !== $module->key()) {
            $this->reject($module, 'invalid_key');
            return false;
        }

        if (isset($this->modules[$key])) {
            $this->reject($module, 'duplicate_key');
            return false;
        }

        if (sanitize_key($module->product()) === '') {
            $this->reject($module, 'invalid_product');
            return false;
        }

        if (!in_array($module->tier(), array(Module::TIER_FREE, Module::TIER_PRO, Module::TIER_EXTENSION), true)) {
            $this->reject($module, 'invalid_tier');
            return false;
        }

        foreach ($module->dependencies() as $dependency) {
            if (!is_string($dependency) || sanitize_key($dependency) !== $dependency || $dependency === $key) {
                $this->reject($module, 'invalid_dependency');
                return false;
            }
        }

        $this->modules[$key] = $module;
        $this->status[$key] = 'registered';

        do_action('ultimate_commerce_module_registered', $key, $module, $this);
        return true;
    }

    public function boot(): void
    {
        foreach (array_keys($this->modules) as $key) {
            $this->bootModule($key, array());
        }
    }

    /** @return array<string, Module> */
    public function all(): array
    {
        return $this->modules;
    }

    public function get(string $key): ?Module
    {
        $key = sanitize_key($key);
        return $this->modules[$key] ?? null;
    }

    public function isBooted(string $key): bool
    {
        return !empty($this->booted[sanitize_key($key)]);
    }

    /** @return array<string, bool> */
    public function states(): array
    {
        $states = array();
        foreach ($this->modules as $key => $module) {
            $states[$key] = $this->isBooted($key);
        }
        return $states;
    }

    /** @return array<string, string> */
    public function issues(): array
    {
        return $this->issues;
    }

    /** @return array<string, array<string, mixed>> */
    public function statuses(): array
    {
        $statuses = array();
        foreach ($this->modules as $key => $module) {
            $statuses[$key] = array(
                'name' => $module->name(),
                'product' => $module->product(),
                'tier' => $module->tier(),
                'dependencies' => $module->dependencies(),
                'compatibility' => $module->compatibility(),
                'settings_schema' => $module->settingsSchema(),
                'assets' => $module->assets(),
                'enabled' => Settings::moduleEnabled($key),
                'booted' => $this->isBooted($key),
                'status' => $this->status[$key] ?? 'registered',
                'issue' => $this->issues[$key] ?? '',
            );
        }
        return $statuses;
    }

    private function bootModule(string $key, array $stack): bool
    {
        if (array_key_exists($key, $this->booted)) {
            return $this->booted[$key];
        }

        if (in_array($key, $stack, true)) {
            $this->block($key, 'circular_dependency:' . implode('>', array_merge($stack, array($key))));
            return false;
        }

        $module = $this->modules[$key] ?? null;
        if (!$module instanceof Module) {
            return false;
        }

        if (!Settings::moduleEnabled($key)) {
            $this->booted[$key] = false;
            $this->status[$key] = 'disabled';
            return false;
        }

        $this->status[$key] = 'booting';
        $stack[] = $key;

        foreach ($module->dependencies() as $dependency) {
            if (!isset($this->modules[$dependency])) {
                $this->block($key, 'missing_dependency:' . $dependency);
                return false;
            }

            if (!$this->bootModule($dependency, $stack)) {
                $this->block($key, 'dependency_unavailable:' . $dependency);
                return false;
            }
        }

        $module->register();
        $this->booted[$key] = true;
        $this->status[$key] = 'booted';
        do_action('ultimate_commerce_module_loaded', $key, $module, $this);
        return true;
    }

    private function block(string $key, string $reason): void
    {
        $this->booted[$key] = false;
        $this->status[$key] = 'blocked';
        if (!isset($this->issues[$key])) {
            $this->issues[$key] = $reason;
            do_action('ultimate_commerce_module_blocked', $key, $reason, $this);
        }
    }

    private function reject(Module $module, string $reason): void
    {
        do_action('ultimate_commerce_module_registration_rejected', $module, $reason, $this);
    }
}
