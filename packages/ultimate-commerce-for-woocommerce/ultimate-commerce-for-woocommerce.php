<?php
/**
 * Plugin Name: Ultimate Commerce for WooCommerce
 * Plugin URI: https://badotter.io/ultimate-commerce
 * Description: A modular commerce experience enhancement platform for WooCommerce.
 * Version: 0.2.0
 * Requires at least: 6.6
 * Requires PHP: 8.1
 * Requires Plugins: woocommerce
 * Author: Bad Otter Labs
 * Author URI: https://badotter.io
 * Text Domain: ultimate-commerce-for-woocommerce
 * Domain Path: /languages
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 */

defined('ABSPATH') || exit;

define('ULTIMATE_COMMERCE_VERSION', '0.2.0');
define('ULTIMATE_COMMERCE_SCHEMA_VERSION', '1');
define('ULTIMATE_COMMERCE_MODULE_API_VERSION', '1.0.0');
define('ULTIMATE_COMMERCE_SECURITY_API_VERSION', '1.0.0');
define('ULTIMATE_COMMERCE_AUDIT_API_VERSION', '1.0.0');
define('ULTIMATE_COMMERCE_PRIVACY_API_VERSION', '1.0.0');
define('ULTIMATE_COMMERCE_EXECUTION_API_VERSION', '1.0.0');
define('ULTIMATE_COMMERCE_STOREFRONT_API_VERSION', '1.0.0');
define('ULTIMATE_COMMERCE_CART_API_VERSION', '1.0.0');
define('ULTIMATE_COMMERCE_FILE', __FILE__);
define('ULTIMATE_COMMERCE_DIR', plugin_dir_path(__FILE__));
define('ULTIMATE_COMMERCE_URL', plugin_dir_url(__FILE__));

require_once ULTIMATE_COMMERCE_DIR . 'src/autoload.php';

register_activation_hook(__FILE__, array(\BadOtter\UltimateCommerce\Plugin::class, 'activate'));
add_action('plugins_loaded', array(\BadOtter\UltimateCommerce\Plugin::class, 'boot'), 5);
