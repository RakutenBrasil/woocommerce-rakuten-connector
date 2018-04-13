<?php
/**
 * Bank Billet - HTML email instructions.
 *
 * @author  Rakuten Pay
 * @package WooCommerce_Rakuten_Pay/Templates
 * @version 2.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>

<h2><?php esc_html_e( 'Payment', 'woocommerce-rakuten-pay' ); ?></h2>

<p class="order_details"><?php esc_html_e( 'Please use the link below to view your banking billet, you can print and pay in your internet banking or in a lottery retailer:', 'woocommerce-rakuten-pay' ); ?><br /><a class="button" href="<?php echo esc_url( $url ); ?>" target="_blank"><?php esc_html_e( 'Pay the banking billet', 'woocommerce-rakuten-pay' ); ?></a><br /><?php esc_html_e( 'After we receive the banking billet payment confirmation, your order will be processed.', 'woocommerce-rakuten-pay' ); ?></p>
