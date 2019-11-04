<?php
/**
 * GenPay My Account actions
 *
 * @package WooCommerce_Rakuten_Pay/Frontend
 */

if ( ! defined( 'ABSPATH' ) ) {
  exit;
}

/**
 * WC_Rakuten_Pay_My_Account class.
 */
class WC_Rakuten_Pay_My_Account {

  /**
   * Initialize my account actions.
   */
  public function __construct() {
    add_filter( 'woocommerce_my_account_my_orders_actions', array( $this, 'my_orders_banking_billet_link' ), 10, 2 );
  }

  /**
   * Add banking billet link/button in My Orders section on My Accout page.
   *
   * @param array    $actions Actions.
   * @param WC_Order $order   Order data.
   *
   * @return array
   */
  public function my_orders_banking_billet_link( $actions, $order ) {
    if ( 'rakuten-pay-banking-billet' !== $order->get_payment_method() ) {
      return $actions;
    }
    if ( ! in_array( $order->get_status(), array( 'pending', 'on-hold' ), true ) ) {
      return $actions;
    }
    $data = get_post_meta( $order->get_id(), '_wc_rakuten_pay_transaction_data', true );
    if ( ! empty( $data['billet_url'] ) ) {
      $actions['billet'] = array(
        'url'  => $data['billet_url'],
        'name' => __( 'Billet', 'woocommerce-rakuten-pay' )
      );
    }
    return $actions;
  }
}

new WC_Rakuten_Pay_My_Account();
