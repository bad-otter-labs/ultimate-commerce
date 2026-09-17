<?php

if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

require_once __DIR__ . '/src/autoload.php';

\BadOtter\UltimateCommerce\Privacy\Uninstall::run();
