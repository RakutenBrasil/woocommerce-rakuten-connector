<?php
/**
 * Plugin Name: WooCommerce Rakuten Log
 * Plugin URI: http://github.com/RakutenBrasil/woocommerce-rakuten-log
 * Description: Gateway de logística Rakuten Log para WooCommerce.
 * Author: Rakuten Log
 * Author URI: https://rakuten.com.br/
 * Version: 1.0.0
 * License: GPLv2 or later
 * Text Domain: woocommerce-rakuten-log
 * Domain Path: /languages/
 *
 * @package WC_Rakuten_Log
 */

if (!defined('ABSPATH')){
    exit;
}
?>

<h2 id="wc-rakuten-log-tracking" class="wc-rakuten-log-tracking__title"><?php esc_html_e( 'Rakuten Log delivery tracking', 'woocommerce-rakuten-log' ); ?></h2>
<div>
    <label><?php echo esc_html_e('Tracking Code:', 'woocommerce-rakuten-log') ?></label>
    <span><?php echo esc_html($tracking_code) ?></span>
</div>
<div>
    <label><?php echo esc_html_e('Tracking Url:', 'woocommerce-rakuten-log') ?></label>
    <a href="<?php echo esc_html($tracking_url) ?>"><?php esc_html_e('Link', 'woocommerce-rakuten-log')?></a>
</div>
<br>
