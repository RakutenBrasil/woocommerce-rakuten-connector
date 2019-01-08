<?php
/**
 * Plugin Name: WooCommerce Rakuten Pay
 * Plugin URI: http://github.com/RakutenBrasil/woocommerce-rakuten-pay
 * Description: Gateway de pagamento Rakuten Pay e Rakuten Logistics para WooCommerce.
 * Author: Rakuten Pay
 * Author URI: https://rakuten.com.br/
 * Version: 1.1.2
 * License: GPLv2 or later
 * Text Domain: woocommerce-rakuten-pay
 * Domain Path: /languages/
 *
 * @package WooCommerce_Rakuten_Pay
 */

if ( ! defined( 'ABSPATH' ) ) {
  exit;
}

if ( ! class_exists( 'WC_Rakuten_Pay' ) ) :

  /**
   * WooCommerce WC_Rakuten_Pay main class.
   */
  class WC_Rakuten_Pay {

    /**
     * Plugin version.
     *
     * @var string
     */
    const VERSION = '1.0.0';

    /**
     * Instance of this class.
     *
     * @var object
     */
    protected static $instance = null;

    /**
     * Initialize the plugin public actions.
     */
    private function __construct() {
      // Load plugin text domain.
      add_action( 'init', array( $this, 'load_plugin_textdomain' ) );

      // Checks with WooCommerce is installed.
      if ( class_exists( 'WC_Payment_Gateway' ) ) {
        $this->includes();

        add_filter( 'woocommerce_payment_gateways', array( $this, 'add_gateway' ) );
        add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), array( $this, 'plugin_action_links' ) );

        // add the action
        add_action( 'wp_mail_failed', function($wp_error) {
            return error_log(print_r($wp_error, true));
        }, 10, 1 );

      } else {
        add_action( 'admin_notices', array( $this, 'woocommerce_missing_notice' ) );
      }
    }

    /**
     * Return an instance of this class.
     *
     * @return object A single instance of this class.
     */
    public static function get_instance() {
      // If the single instance hasn't been set, set it now.
      if ( null === self::$instance ) {
        self::$instance = new self;
      }

      return self::$instance;
    }

    /**
     * Includes.
     */
    private function includes() {
      include_once dirname( __FILE__ ) . '/includes/class-wc-rakuten-pay-api.php';
      include_once dirname( __FILE__ ) . '/includes/class-wc-rakuten-pay-my-account.php';
      include_once dirname( __FILE__ ) . '/includes/class-wc-rakuten-pay-admin-customizations.php';
      include_once dirname( __FILE__ ) . '/includes/class-wc-rakuten-pay-banking-billet-gateway.php';
      include_once dirname( __FILE__ ) . '/includes/class-wc-rakuten-pay-credit-card-gateway.php';
    }

    /**
     * Load the plugin text domain for translation.
     */
    public function load_plugin_textdomain() {
      load_plugin_textdomain( 'woocommerce-rakuten-pay', false, dirname( plugin_basename( __FILE__ ) ) . '/languages/' );
    }

    /**
     * Get templates path.
     *
     * @return string
     */
    public static function get_templates_path() {
      return plugin_dir_path( __FILE__ ) . 'templates/';
    }

    /**
     * Add the gateway to WooCommerce.
     *
     * @param  array $methods WooCommerce payment methods.
     *
     * @return array
     */
    public function add_gateway( $methods ) {
      $methods[] = 'WC_Rakuten_Pay_Banking_Billet_Gateway';
      $methods[] = 'WC_Rakuten_Pay_Credit_Card_Gateway';

      return $methods;
    }

    /**
     * Action links.
     *
     * @param  array $links Plugin links.
     *
     * @return array
     */
    public function plugin_action_links( $links ) {
      $plugin_links = array();

      $banking_billet = 'wc_rakuten_pay_banking_billet_gateway';
      $credit_card    = 'wc_rakuten_pay_credit_card_gateway';

      $plugin_links[] = '<a href="' . esc_url( admin_url( 'admin.php?page=wc-settings&tab=checkout&section=' . $banking_billet ) ) . '">' . __( 'Bank Billet Settings', 'woocommerce-rakuten-pay' ) . '</a>';

      $plugin_links[] = '<a href="' . esc_url( admin_url( 'admin.php?page=wc-settings&tab=checkout&section=' . $credit_card ) ) . '">' . __( 'Credit Card Settings', 'woocommerce-rakuten-pay' ) . '</a>';

      return array_merge( $plugin_links, $links );
    }

    /**
     * WooCommerce fallback notice.
     */
    public function woocommerce_missing_notice() {
      include dirname( __FILE__ ) . '/includes/admin/views/html-notice-missing-woocommerce.php';
    }
  }

  add_action( 'plugins_loaded', array( 'WC_Rakuten_Pay', 'get_instance' ) );

endif;

define('WC_RAKUTEN_LOG_VERSION', '1.0.0');
define('WC_RAKUTEN_LOG_PLUGIN_FILE', __FILE__);
define('WC_RAKUTEN_LOG_SANDBOX_API_URL', 'https://oneapi-sandbox.rakutenpay.com.br/logistics/');
define('WC_RAKUTEN_LOG_PRODUCTION_API_URL', 'https://api.rakuten.com.br/logistics/');

if (!class_exists('WC_Rakuten_Log')) {
<<<<<<< HEAD
    include_once dirname(__FILE__) . 'woocommerce-rakuten-log/includes/class-wc-rakuten-log.php';
=======
    include_once dirname(__FILE__) . '/woocommerce-rakuten-log/includes/class-wc-rakuten-log.php';
>>>>>>> master

    add_action('plugins_loaded', array('WC_Rakuten_Log', 'init'));
}