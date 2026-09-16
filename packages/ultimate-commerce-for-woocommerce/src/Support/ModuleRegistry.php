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

    public function register(Module $module): void
    {
        $key = sanitize_key($module->key());
        if ($key === '' || isset($this->modules[$key])) {
            return;
        }

        $this->modules[$key] = $module;
    }

    public function boot(): void
    {
        foreach ($this->modules as $key => $module) {
            if (!Settings::moduleEnabled($key)) {
                $this->booted[$key] = false;
                continue;
            }

            $module->register();
            $this->booted[$key] = true;
            do_action('uc_module_loaded', $key, $module);
        }
    }

    /** @return array<string, Module> */
    public function all(): array
    {
        return $this->modules;
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
}
