<?php
/**
 * GenPay Admin Customizations
 *
 * @package WooCommerce_Rakuten_Pay/Frontend
 */

if ( ! defined( 'ABSPATH' ) ) {
  exit;
}

/**
 * WC_Rakuten_Pay_Admin_Customizations class.
 */
class WC_Rakuten_Pay_Admin_Customizations {

  /**
   * Initialize my account actions.
   */
  public function __construct() {
    add_action( 'admin_enqueue_scripts', array( $this, 'load_admin_scripts' ) );
  }

  /**
   * Load admin scripts.
   *  Mostly to be used on refund
   */
  public function load_admin_scripts() {
    $suffix = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? '' : '.min';

    wp_enqueue_script( 'jquery-inputmask', plugins_url( 'assets/js/jquery.inputmask.min' . '.js', plugin_dir_path( __FILE__ ) ), array ( 'jquery' ), null );
    wp_enqueue_script( 'rakuten-pay-adminn', plugins_url( 'assets/js/admin' . $suffix . '.js', plugin_dir_path( __FILE__ ) ), array( 'jquery', 'jquery-inputmask' ), WC_Rakuten_Pay::VERSION, true );
  }

}

new WC_Rakuten_Pay_Admin_Customizations();
