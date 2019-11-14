<?php
/**
 * Rakuten Pay Banking Billet gateway
 *
 * @package WooCommerce_Rakuten_Pay/Gateway
 */

if ( ! defined( 'ABSPATH' ) ) {
  exit;
}

/**
 * WC_Rakuten_Pay_Banking_Billet_Gateway class.
 *
 * @extends WC_Payment_Gateway
 */
class WC_Rakuten_Pay_Banking_Billet_Gateway extends WC_Payment_Gateway {

  /**
   * Constructor for the gateway.
   */
  public function __construct() {
    $this->id                   = 'rakuten-pay-banking-billet';
    $this->icon                 = apply_filters( 'wc_rakuten_pay_banking_billet_icon', false );
    $this->has_fields           = true;
    $this->title                = __( 'GenPay - Banking Billet', 'woocommerce-rakuten-pay' );
    $this->description          = __( 'Pay with Banking Billet', 'woocommerce-rakuten-pay' );
    $this->method_title         = __( 'GenPay - Banking Billet', 'woocommerce-rakuten-pay' );
    $this->method_description   = __( 'Accept banking billet payments using GenPay.', 'woocommerce-rakuten-pay' );
    $this->view_transaction_url = 'https://dashboard.rakutenpay.com.br/sales/%s';
    $this->supports             = array( 'products', 'refunds' );

    // Load the form fields.
    $this->init_form_fields();

    // Load the settings.
    $this->init_settings();

    // Define user set variables.
    $this->document       = $this->get_option( 'document' );
    $this->api_key        = $this->get_option( 'api_key' );
    $this->signature_key  = $this->get_option( 'signature_key' );
    $this->debug          = $this->get_option( 'debug' );
    $this->environment    = $this->get_option( 'environment' );

    // Active logs.
    if ( 'yes' === $this->debug ) {
      $this->log = new WC_Logger();
    }

    // Set the API.
    $this->api = new WC_Rakuten_Pay_API( $this );

    // Actions.
    add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'process_admin_options' ) );
    add_action( 'wp_enqueue_scripts', array( $this, 'checkout_scripts' ) );
    add_action( 'woocommerce_thankyou_' . $this->id, array( $this, 'thankyou_page' ) );
    add_action( 'woocommerce_email_after_order_table', array( $this, 'email_instructions' ), 10, 3 );
    add_action( 'woocommerce_api_wc_rakuten_pay_banking_billet_gateway', array( $this, 'banking_billet_handler' ) );
    add_action( 'woocommerce_order_status_cancelled', array( $this, 'cancel_banking_billet_transaction' ) );
    add_action( 'admin_enqueue_scripts', array( $this, 'load_admin_scripts' ) );
    // A Hack to allow insert script tag of type text/template just for additional refund banking data.
    add_action( 'woocommerce_order_item_add_action_buttons', array( $this, 'refund_banking_data_fields' ) );
  }

  /**
   * Admin page.
   */
  public function admin_options() {
    include dirname( __FILE__ ) . '/admin/views/html-admin-page.php';
  }

  /**
   * Check if the gateway is available to take payments.
   *
   * @return bool
   */
  public function is_available() {
    return parent::is_available() && ! empty( $this->document ) && ! empty( $this->api_key ) && ! empty( $this->signature_key ) && $this->api->using_supported_currency();
  }

  /**
   * Settings fields.
   */
  public function init_form_fields() {
    $this->form_fields = array(
      'enabled' => array(
        'title'   => __( 'Enable/Disable', 'woocommerce-rakuten-pay' ),
        'type'    => 'checkbox',
        'label'   => __( 'Enable GenPay Banking Billet', 'woocommerce-rakuten-pay' ),
        'default' => 'no',
      ),
      'integration' => array(
        'title'       => __( 'Integration Settings', 'woocommerce-rakuten-pay' ),
        'type'        => 'title',
        'description' => '',
      ),
      'document' => array(
        'title'             => __( 'GenPay Document', 'woocommerce-rakuten-pay' ),
        'type'              => 'text',
        'description'       => sprintf( __( 'Please enter the document of your store.', 'woocommerce-rakuten-pay' ), '<a href="https://dashboard.genpay.com.br/">' . __( 'GenPay Dashboard > My Account page', 'woocommerce-rakuten-pay' ) . '</a>' ),
        'default'           => '',
        'custom_attributes' => array(
          'required' => 'required',
        ),
      ),
      'api_key' => array(
        'title'             => __( 'GenPay API Key', 'woocommerce-rakuten-pay' ),
        'type'              => 'text',
        'description'       => sprintf( __( 'Please enter your GenPay API Key. This is needed to process the payment and notifications. Is possible get your API Key in %s.', 'woocommerce-rakuten-pay' ), '<a href="https://dashboard.genpay.com.br/">' . __( 'GenPay Dashboard > My Account page', 'woocommerce-rakuten-pay' ) . '</a>' ),
        'default'           => '',
        'custom_attributes' => array(
          'required' => 'required',
        ),
      ),
      'signature_key' => array(
        'title'             => __( 'GenPay Signature Key', 'woocommerce-rakuten-pay' ),
        'type'              => 'text',
        'description'       => sprintf( __( 'Please enter your GenPay Signature key. This is needed to process the payment. Is possible get your Signature Key in %s.', 'woocommerce-rakuten-pay' ), '<a href="https://dashboard.genpay.com.br/">' . __( 'GenPay Dashboard > My Account page', 'woocommerce-rakuten-pay' ) . '</a>' ),
        'default'           => '',
        'custom_attributes' => array(
          'required' => 'required',
        ),
      ),
			'environment' => array(
				'title'       => __( 'Environment', 'woocommerce-rakuten-pay' ),
				'type'        => 'select',
				'description' => sprintf( __( 'GenPay has two environemnts, th e Sandbox used to make test transactions, and Production used for real transactions.', 'woocommerce-rakuten-pay' ) ),
				'default'     => 'production',
				'options'     => array(
					'production'  => sprintf( __( 'Production', 'woocommerce-raktuten-pay' ) ),
					'sandbox'     => sprintf( __( 'Sandbox', 'woocommerce-raktuten-pay' ) )
				)
			),
      'testing' => array(
        'title'       => __( 'Gateway Testing', 'woocommerce-rakuten-pay' ),
        'type'        => 'title',
        'description' => '',
      ),
      'debug' => array(
        'title'       => __( 'Debug Log', 'woocommerce-rakuten-pay' ),
        'type'        => 'checkbox',
        'label'       => __( 'Enable logging', 'woocommerce-rakuten-pay' ),
        'default'     => 'no',
        'description' => sprintf( __( 'Log GenPay events, such as API requests. You can check the log in %s', 'woocommerce-rakuten-pay' ), '<a href="' . esc_url( admin_url( 'admin.php?page=wc-status&tab=logs&log_file=' . esc_attr( $this->id ) . '-' . sanitize_file_name( wp_hash( $this->id ) ) . '.log' ) ) . '">' . __( 'System Status &gt; Logs', 'woocommerce-rakuten-pay' ) . '</a>' ),
      ),
    );
  }

  /**
   * Checkout scripts.
   */
  public function checkout_scripts() {
    if ( is_checkout() ) {
      $suffix = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? '' : '.min';
      wp_enqueue_script( 'rakuten-pay-banking-billet', plugins_url( 'assets/js/banking-billet' . '.js', plugin_dir_path( __FILE__ ) ), array( 'jquery', 'jquery-blockui', 'rakuten-pay-library' ), WC_Rakuten_Pay::VERSION, true );
    }
  }

  /**
   * Load admin scripts.
   *  Mostly to be used on refund
   */
  public function load_admin_scripts() {
    $suffix = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? '' : '.min';

    $var = wp_enqueue_script( 'rakuten-pay-admin', plugins_url( 'assets/js/admin' . $suffix . '.js', plugin_dir_path( __FILE__ ) ), array( 'jquery' ), WC_Rakuten_Pay::VERSION, true );
    error_log('load_admin_scripts...');
    error_log(print_r($var, true));
  }

  /**
   * Payment fields.
   */
  public function payment_fields() {
    if ( $description = $this->get_description() ) {
      echo wp_kses_post( wpautop( wptexturize( $description ) ) );
    }

    wc_get_template(
      'banking-billet/checkout-instructions.php',
      array(),
      'woocommerce/rakuten-pay/',
      WC_Rakuten_Pay::get_templates_path()
    );
  }


  /**
   * Refund banking data
   */
  public function refund_banking_data_fields( $order ) {
    if ( 'rakuten-pay-banking-billet' !== $order->get_payment_method() ) {
      return;
    }

    wc_get_template(
      'banking-billet/refund-banking-data.php',
      array(),
      'woocommerce/rakuten-pay/',
      WC_Rakuten_Pay::get_templates_path()
    );
  }

  /**
   * Process the payment.
   *
   * @param int $order_id Order ID.
   *
   * @return array Redirect data.
   */
  public function process_payment( $order_id ) {
    return $this->api->process_regular_payment( $order_id );
  }

  /**
   * Process the payment refund.
   *
   * @param int    $order_id  Order ID.
   * @param float  $amount    Amount to refund.
   * @param string $reason    Reason whereby the refund has been done.
   *
   * @return array Redirect data.
   */
  public function process_refund( $order_id, $amount = null, $reason = '' ) {
    return $this->api->process_refund( $order_id, $amount, $reason );
  }

  /**
   * Thank You page message.
   *
   * @param int $order_id Order ID.
   */
  public function thankyou_page( $order_id ) {
    $order = wc_get_order( $order_id );
    $data  = get_post_meta( $order_id, '_wc_rakuten_pay_transaction_data', true );

    if ( ! isset( $data['billet_url'] ) ) {
      return;
    }

    if ( ! in_array( $order->get_status(), array( 'pending', 'on-hold' ) ) ) {
      return;
    }

    wc_get_template(
      'banking-billet/payment-instructions.php',
      array(
        'url' => $data['billet_url'],
      ),
      'woocommerce/rakuten-pay/',
      WC_Rakuten_Pay::get_templates_path()
    );
  }

  /**
   * Add content to the WC emails.
   *
   * @param  object $order         Order object.
   * @param  bool   $sent_to_admin Send to admin.
   * @param  bool   $plain_text    Plain text or HTML.
   *
   * @return string                Payment instructions.
   */
  public function email_instructions( $order, $sent_to_admin, $plain_text = false ) {
    if ( $sent_to_admin || ! in_array( $order->get_status(), array( 'pending', 'on-hold' ), true ) || $this->id !== $order->get_payment_method() ) {
      return;
    }

    $data = get_post_meta( $order->get_id(), '_wc_rakuten_pay_transaction_data', true );

    if ( isset( $data['billet_url'] ) ) {
      $email_type = $plain_text ? 'plain' : 'html';

      wc_get_template(
        'banking-billet/emails/' . $email_type . '-instructions.php',
        array(
          'url' => $data['billet_url'],
        ),
        'woocommerce/rakuten-pay/',
        WC_Rakuten_Pay::get_templates_path()
      );
    }
  }

  /**
   * Banking Billet Handler
   * Can be the ipn request or billet url redirection
   */
  public function banking_billet_handler() {
    if ( isset( $_GET['billet'] ) ) {
      $this->api->process_banking_billet( $_GET['billet'] );
    } else {
      $this->api->ipn_handler();
    }
  }

  /**
   * Cancels Banking Billet transaction.
   *
   * @param int $order_id Order ID.
   */
  public function cancel_banking_billet_transaction( $order_id ) {
    error_log('cancel_banking_billet...');
    $order = wc_get_order( $order_id );

    if ( get_post_meta( $order_id, '_wc_rakuten_pay_order_cancelled', true ) ) {
      return;
    }

    if ( ! $this->api->is_banking_billet_payment_method( $order ) ) {
      return;
    }

    $this->api->cancel_transaction( $order );
  }
}
