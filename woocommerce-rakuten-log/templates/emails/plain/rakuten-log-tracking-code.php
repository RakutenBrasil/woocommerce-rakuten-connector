<?php
/**
 * Plugin Name: WooCommerce GenLog
 * Plugin URI: http://github.com/GenCommBrasil/woocommerce-rakuten-log
 * Description: Gateway de logística GenLog para WooCommerce.
 * Author: GenLog
 * Author URI: https://rakuten.com.br/
 * Version: 1.0.0
 * License: GPLv2 or later
 * Text Domain: woocommerce-rakuten-log
 * Domain Path: /languages/
 *
 * @package WC_Rakuten_Log
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
echo "= " . $email_heading . " =\n\n";
echo wptexturize( $tracking_message ) . "\n\n";
echo __( 'For your reference, your order details are shown below.', 'woocommerce-rakuten-log' ) . "\n\n";
echo "=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=\n\n";

do_action( 'woocommerce_email_order_details', $order, $sent_to_admin, $plain_text, $email );
echo "\n=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=\n\n";

do_action( 'woocommerce_email_order_meta', $order, $sent_to_admin, $plain_text, $email );

do_action( 'woocommerce_email_customer_details', $order, $sent_to_admin, $plain_text, $email );
echo "\n=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=\n\n";
echo apply_filters( 'woocommerce_email_footer_text', get_option( 'woocommerce_email_footer_text' ) );