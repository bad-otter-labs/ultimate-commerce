<?php

namespace BadOtter\UltimateCommerce\Contracts;

defined('ABSPATH') || exit;

abstract class AbstractModule implements Module
{
    /** @return list<string> */
    public function dependencies(): array
    {
        return array();
    }

    /** @return array<string, string> */
    public function compatibility(): array
    {
        return array('module_api' => Module::CONTRACT_VERSION);
    }

    /** @return array<string, mixed> */
    public function settingsSchema(): array
    {
        return array();
    }

    /** @return array<string, list<string>> */
    public function assets(): array
    {
        return array(
            'frontend' => array(),
            'admin' => array(),
        );
    }
}
