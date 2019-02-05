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

if ( !defined('ABSPATH' )){
    exit;
}
?>

<?php do_action('woocommerce_email_header', $email_heading, $email); ?>

<?php echo wptexturize( wpautop( $tracking_message ) ); ?>

<p><?php esc_html_e( 'For your reference, your order details are shown below.', 'woocommerce-rakuten-log' ); ?></p>

<?php
/**
 * @hooked WC_Emails::order_details() Shows the order details table.
 * @hooked WC_Structured_Data::generate_order_data() Generates structured data.
 * @hooked WC_Structured_Data::output_structured_data() Outputs structured data.
 */
do_action( 'woocommerce_email_order_details', $order, $sent_to_admin, $plain_text, $email );
/**
 * Order meta.
 *
 * @hooked WC_Emails::order_meta() Shows order meta data.
 */
do_action( 'woocommerce_email_order_meta', $order, $sent_to_admin, $plain_text, $email );
/**
 * Customer details.
 *
 * @hooked WC_Emails::customer_details() Shows customer details
 * @hooked WC_Emails::email_address() Shows email address
 */
do_action( 'woocommerce_email_customer_details', $order, $sent_to_admin, $plain_text, $email );
/**
 * Email footer.
 *
 * @hooked WC_Emails::email_footer() Output the email footer.
 */
do_action( 'woocommerce_email_footer', $email );