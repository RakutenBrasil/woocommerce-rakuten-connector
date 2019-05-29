<?php
/**
 * Plugin Name: WooCommerce Rakuten Connector
 * Plugin URI: http://github.com/RakutenBrasil/woocommerce-rakuten-pay
 * Description: Gateway de pagamento Rakuten Pay e Rakuten Logistics para WooCommerce.
 * Author: Rakuten Pay
 * Author URI: https://rakuten.com.br/
 * Version: 1.1.11
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
    const VERSION = '1.1.11';

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
      add_action('admin_menu','rakuten_connector_menu');

      function rakuten_connector_menu() {
        add_menu_page( 'Rakuten Connector Plugin','Rakuten Connector','manage_options','rakuten_connector','rakuten_connector_page_menu',plugins_url('rakuten-favicon.png', __FILE__) );
        add_submenu_page( 'rakuten_connector', 'Rakuten Connector', 'Configurações','manage_options', 'rakuten_connector' );
        add_submenu_page( 'rakuten_connector', 'Connector Submenu', 'Rakuten Pay Boleto','manage_options', 'wc-settings&tab=checkout&section=wc_rakuten_pay_banking_billet_gateway','rakuten_connector_page_menu' );
        add_submenu_page( 'rakuten_connector', 'Connector Submenu', 'Rakuten Pay Cartão de Crédito','manage_options', 'wc-settings&tab=checkout&section=wc_rakuten_pay_credit_card_gateway','rakuten_connector_page_menu' );
      }

      function rakuten_connector_page_menu() {
        echo "
        <style>
          a { color: #333; text-decoration: none; }
          a:hover { text-decoration: underline; }
          
          .title {
            text-align: center;
            color: #c4c4c4;
            font-weight: bolder !important;
            transition: .2s all ease-in-out;
          }

          .wrap {
            display: flex;
            width: 100%;
            justify-content: center;
          }

          .box {
            display: flex-wrap;
            justify-content: center;
            align-items: center;
            background: #fff;
            width:30%;
            height: 170px;
            padding: 50px;
            margin: 40px 40px 20px 0;
            text-align: center;
            float: left;
            box-shadow: 2px 2px 10px rgba(0,0,0,0.1);
            border-radius: 5px;
            transition: .2s all ease-in-out;
          }
          
          .box:hover {
            box-shadow: 2px 5px 20px rgba(0,0,0,0.3);
          }
          .box:hover h1 {
            color: #bf0000;
          }
          
          .box-full:hover {
            box-shadow: 2px 5px 20px rgba(0,0,0,0.3);
          }
          .box-full:hover h1 {
            color: #bf0000;
          }
          .box-logo {
            width: 95%;
            padding: 20px;
          }
          .box-full {
            text-align: center;
            width: 50%;
            padding: 50px;
            background: #fff;
            margin: 10px auto;
            box-shadow: 2px 2px 10px rgba(0,0,0,0.1);
            border-radius: 5px;
            transition: .2s all ease-in-out;
          }
          .submit { display: table; margin: 20px auto 5px }
          
          @media screen and (max-width: 479px){
            .box {
              width: 72%;
            }
          }
        </style>

        <br />
        <div class='box-logo'>
          <img src='" . plugins_url('rakuten-connector-logo.png', __FILE__) . "' />
          <hr>
        </div>
        <!-- Rakuten Pay configuration admin menu page-->
        <div class='wrap'>
          <div class='box'>
            <h1 class='title'>Rakuten Pay</h1>
            <hr>
            <br />
            <h3><a href='" . esc_url( admin_url( 'admin.php?page=wc-settings&tab=checkout&section=wc_rakuten_pay_banking_billet_gateway' ) ) . "' >" . __( 'Bank Billet Settings', 'woocommerce-rakuten-pay' ) . "</a></h3>
            <h3><a href='" . esc_url( admin_url( 'admin.php?page=wc-settings&tab=checkout&section=wc_rakuten_pay_credit_card_gateway' ) ) . "'>" . __( 'Credit Card Settings', 'woocommerce-rakuten-pay' ) . "</a></h3>
          </div>
        ";
        $query = $GLOBALS['wpdb']->get_results( "SELECT instance_id,method_id FROM {$GLOBALS['wpdb']->prefix}woocommerce_shipping_zone_methods WHERE method_id = 'rakuten-log' " );
        // get the Rakuten Log id
        foreach ($query as $dado) {
          echo "
            <!-- Rakuten Logistics configuration admin menu page -->
            <div class='box'>
              <h1 class='title'>Rakuten Log</h1>
              <hr>
              <br />
              <h3><a href='admin.php?page=wc-settings&tab=shipping&instance_id={$dado->instance_id}' >Configurações de Entrega</a></h3>
              <h3><a href='http://logistics-sandbox.rakuten.com.br' target='_blank'>Painel Rakuten Logistics</a></h3>
            </div>
          </div>
          ";
        }

        // CEP checkout validation configuration admin menu page
        $value = get_option('rakuten_cep_validation');

//        if ( empty( $value )) {
//            update_option('rakuten_cep_validation', '1');
//        }
        ?>

        <div class='wrap'>
          <div class='box-full'>
            <h1 class='title'>Validação CEP no checkout</h1>
            <hr>
            <br />

                <form method='post' action='admin.php?page=rakuten_connector'>
                <?php

                if ( $value == "1" ) { ?>

                    Habilitar:
                    <select name="cep_validation_1" id="cep">
                        <?php
                            $options = array( "1" => "Sim", "0" => "Não" );
                            foreach ( $options as $option ) {
                                echo "<option value='" . print_r($option, true) . "'>" . print_r($option, true) . "</option>";
                            }
                        ?>

                    </select>

                    <?php

                    if( ! empty($_POST['cep_validation_1']) ){

                        switch ( $_POST['cep_validation_1'] ){
                            case 'Sim':
                                echo "<script>console.log('1 checked isset')</script>";
                                update_option('rakuten_cep_validation', '1');
                                break;
                                $value = get_option('rakuten_cep_validation');

                            case 'Não':

                                update_option('rakuten_cep_validation', '0');

                                break;
                        }

                        header('location: admin.php?page=rakuten_connector');
                    } else {
                        submit_button();
                    }

                    ?>
                </form>
            <?php

            ?>

          </div>
        </div>
            <?php } else { ?>
                    Habilitar:

                    <select name="cep_validation_0">
                        <?php

                        $options = array( "0" => "Não", "1" => "Sim" );
                        foreach ( $options as $option ) {
                            echo "<option value='" . print_r($option, true) . "' id='". print_r($option, true) ."'>" . print_r($option, true) . "</option>";
                        }

                        ?>
                    </select>

                    <?php

                        if( ! empty($_POST['cep_validation_0']) ){

                            switch ( $_POST['cep_validation_0'] ){
                                case 'Sim':
                                    echo "<script>console.log('1 checked isset')</script>";
                                    update_option('rakuten_cep_validation', '1');
                                    break;
                                    $value = get_option('rakuten_cep_validation');
                                    echo $value;
                                case 'Não':
                                    echo "<script>console.log('0 unchecked isset')</script>";
                                    update_option('rakuten_cep_validation', '0');
                                    echo $value;
                                    break;
                            }

                            header('location: admin.php?page=rakuten_connector');
                        } else {
                            submit_button();
                        }
                    ?>
                </form>

          </div>
        </div>
        <?php   }
      }

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
      include_once dirname( __FILE__ ) . '/includes/class-wc-rakuten-pay-order-details.php';
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
    include_once dirname(__FILE__) . '/woocommerce-rakuten-log/includes/class-wc-rakuten-log.php';

    add_action('plugins_loaded', array('WC_Rakuten_Log', 'init'));
}
