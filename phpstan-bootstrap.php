<?php

declare(strict_types=1);

// Runtime constants defined by the plugin entry file. Static analysis loads this
// bootstrap before analysing class files that reference those constants.
defined('ULTIMATE_COMMERCE_DIR') || define(
    'ULTIMATE_COMMERCE_DIR',
    __DIR__ . '/packages/ultimate-commerce-for-woocommerce/'
);
defined('ULTIMATE_COMMERCE_URL') || define(
    'ULTIMATE_COMMERCE_URL',
    'https://example.invalid/wp-content/plugins/ultimate-commerce-for-woocommerce/'
);
defined('ULTIMATE_COMMERCE_VERSION') || define('ULTIMATE_COMMERCE_VERSION', '0.2.0');
