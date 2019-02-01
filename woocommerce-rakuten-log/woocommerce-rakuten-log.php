<?php
/**
 * Plugin Name: WooCommerce Rakuten Log
 * Plugin URI: http://github.com/RakutenBrasil/woocommerce-rakuten-log
 * Description: Gateway de logística Rakuten Log para WooCommerce.
 * Author: Rakuten Log
 * Author URI: https://rakuten.com.br/
 * Version: 1.1.3
 * License: GPLv2 or later
 * Text Domain: woocommerce-rakuten-log
 * Domain Path: /languages/
 *
 * @package WC_Rakuten_Log
 */

if (!defined('ABSPATH')) {
    exit;
}

define('WC_RAKUTEN_LOG_VERSION', '1.0.0');
define('WC_RAKUTEN_LOG_PLUGIN_FILE', __FILE__);
define('WC_RAKUTEN_LOG_SANDBOX_API_URL', 'https://oneapi-sandbox.rakutenpay.com.br/logistics/');
define('WC_RAKUTEN_LOG_PRODUCTION_API_URL', 'https://api.rakuten.com.br/logistics/');

if (!class_exists('WC_Rakuten_Log')) {
    include_once dirname(__FILE__) . '/includes/class-wc-rakuten-log.php';

    add_action('plugins_loaded', array('WC_Rakuten_Log', 'init'));
}
