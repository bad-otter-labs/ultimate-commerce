<?php
/**
 * Plugin Name: Ultimate Commerce
 * Plugin URI: https://badotter.io/ultimate-commerce
 * Description: A modular enhancement platform for WooCommerce storefronts, customer journeys and commerce operations.
 * Version: 0.1.3
 * Requires at least: 6.6
 * Requires PHP: 8.1
 * Requires Plugins: woocommerce
 * Author: Bad Otter Labs
 * Author URI: https://badotter.io
 * Text Domain: ultimate-commerce
 * Update URI: https://badotter.io/ultimate-commerce
 */

defined('ABSPATH') || exit;

define('ULTIMATE_COMMERCE_VERSION', '0.1.3');
define('ULTIMATE_COMMERCE_SCHEMA_VERSION', '1');
define('ULTIMATE_COMMERCE_FILE', __FILE__);
define('ULTIMATE_COMMERCE_DIR', plugin_dir_path(__FILE__));
define('ULTIMATE_COMMERCE_URL', plugin_dir_url(__FILE__));

require_once ULTIMATE_COMMERCE_DIR . 'src/autoload.php';

register_activation_hook(__FILE__, array(\BadOtter\UltimateCommerce\Plugin::class, 'activate'));
add_action('plugins_loaded', array(\BadOtter\UltimateCommerce\Plugin::class, 'boot'), 5);
