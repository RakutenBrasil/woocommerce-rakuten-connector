<?php
/**
 * Bank Billet - Payment instructions.
 *
 * @author  Rakuten Pay
 * @package WooCommerce_Rakuten_Pay/Templates
 * @version 2.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>

<div class="woocommerce-message">
	<span><a class="button" href="<?php echo esc_url( $url ); ?>" target="_blank"><?php esc_html_e( 'Pay the banking billet', 'woocommerce-rakuten-pay' ); ?></a><?php esc_html_e( 'Please click in the following button to view your banking billet.', 'woocommerce-rakuten-pay' ); ?><br /><?php esc_html_e( 'You can print and pay in your internet banking or in a lottery retailer.', 'woocommerce-rakuten-pay' ); ?><br /><?php esc_html_e( 'After we receive the banking billet payment confirmation, your order will be processed.', 'woocommerce-rakuten-pay' ); ?></span>
</div>
