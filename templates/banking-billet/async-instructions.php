<?php
/**
 * Bank Billet - Payment instructions.
 *
 * @author  Rakuten Pay
 * @package WooCommerce_Rakuten_Pay/Templates
 * @version 2.0.11
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>

<div class="woocommerce-message">
	<span><a class="button" href="<?php echo esc_url( $url ); ?>" target="_blank"><?php esc_html_e( 'View order', 'woocommerce-rakuten-pay' ); ?></a><?php esc_html_e( 'Your banking billet is being generated, access your order to view it.', 'woocommerce-rakuten-pay' ); ?><br /></span>
</div>
