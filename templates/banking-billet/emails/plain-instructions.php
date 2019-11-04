<?php
/**
 * Bank Billet - Plain email instructions.
 *
 * @author  GenPay
 * @package WooCommerce_Rakuten_Pay/Templates
 * @version 2.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

esc_html_e( 'Payment', 'woocommerce-rakuten-pay' );

echo "\n\n";

esc_html_e( 'Please use the link below to view your banking billet, you can print and pay in your internet banking or in a lottery retailer:', 'woocommerce-rakuten-pay' );

echo "\n";

echo esc_url( $url );

echo "\n";

esc_html_e( 'After we receive the banking billet payment confirmation, your order will be processed.', 'woocommerce-rakuten-pay' );

echo "\n\n****************************************************\n\n";
