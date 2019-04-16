<?php
/**
 * Rakuten Pay Credit Card gateway
 *
 * @package WooCommerce_Rakuten_Pay/Gateway
 */

if ( ! defined( 'ABSPATH' ) ) {
  exit;
}

/**
 * WC_Rakuten_Pay_Credit_Card_Gateway class.
 *
 * @extends WC_Payment_Gateway
 */
class WC_Rakuten_Pay_Credit_Card_Gateway extends WC_Payment_Gateway_CC {

  /**
   * Constructor for the gateway.
   */
  public function __construct() {
    $this->id                   = 'rakuten-pay-credit-card';
    $this->icon                 = apply_filters( 'wc_rakuten_pay_credit_card_icon', false );
    $this->has_fields           = true;
    $this->title                = __( 'Rakuten Pay - Credit Card', 'woocommerce-rakuten-pay' );
    $this->description          = __( 'Pay with Credit Card', 'woocommerce-rakuten-pay' );
    $this->method_title         = __( 'Rakuten Pay - Credit Card', 'woocommerce-rakuten-pay' );
    $this->method_description   = __( 'Accept credit card payments using Rakuten Pay.', 'woocommerce-rakuten-pay' );
    $this->view_transaction_url = 'https://dashboard.rakuten.com.br/sales/%s';
    $this->supports             = array( 'products', 'refunds' );


    // Load the form fields.
    $this->init_form_fields();

    // Load the settings.
    $this->init_settings();

    // Define user set variables.
    $this->document             = $this->get_option( 'document' );
    $this->api_key              = $this->get_option( 'api_key' );
    $this->signature_key        = $this->get_option( 'signature_key' );
    $this->max_installment      = $this->get_option( 'max_installment' );
    $this->smallest_installment = $this->get_option( 'smallest_installment' );
    $this->free_installments    = $this->get_option( 'free_installments', '1' );
    $this->debug                = $this->get_option( 'debug' );
    $this->environment          = $this->get_option( 'environment' );

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
    add_action( 'woocommerce_api_wc_rakuten_pay_credit_card_gateway', array( $this, 'credit_card_handler' ) );
    add_action( 'woocommerce_order_status_cancelled', array( $this, 'cancel_credit_card_transaction' ) );

    // Filters.
    add_filter( 'woocommerce_checkout_fields', array( $this, 'custom_checkout_fields' ) );
    add_filter( 'woocommerce_default_address_fields', array( $this, 'reorder_custom_default_address_fields' ) );
    add_filter( 'woocommerce_checkout_get_value', array( $this, 'rk_populate_checkout_fields' ), 10, 2 );

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
        'label'   => __( 'Enable Rakuten Pay Credit Card', 'woocommerce-rakuten-pay' ),
        'default' => 'no',
      ),
      'integration' => array(
        'title'       => __( 'Integration Settings', 'woocommerce-rakuten-pay' ),
        'type'        => 'title',
        'description' => '',
      ),
      'document' => array(
        'title'             => __( 'Rakuten Pay Document', 'woocommerce-rakuten-pay' ),
        'type'              => 'text',
        'description'       => sprintf( __( 'Please enter the document of your store.', 'woocommerce-rakuten-pay' ), '<a href="https://dashboard.rakutenpay.com.br/">' . __( 'Rakuten Pay Dashboard > My Account page', 'woocommerce-rakuten-pay' ) . '</a>' ),
        'default'           => '',
        'custom_attributes' => array(
          'required' => 'required',
        ),
      ),
      'api_key' => array(
        'title'             => __( 'Rakuten Pay API Key', 'woocommerce-rakuten-pay' ),
        'type'              => 'text',
        'description'       => sprintf( __( 'Please enter your Rakuten Pay API Key. This is needed to process the payment and notifications. Is possible get your API Key in %s.', 'woocommerce-rakuten-pay' ), '<a href="https://dashboard.rakutenpay.com.br/">' . __( 'Rakuten Pay Dashboard > My Account page', 'woocommerce-rakuten-pay' ) . '</a>' ),
        'default'           => '',
        'custom_attributes' => array(
          'required' => 'required',
        ),
      ),
      'signature_key' => array(
        'title'             => __( 'Rakuten Pay Signature Key', 'woocommerce-rakuten-pay' ),
        'type'              => 'text',
        'description'       => sprintf( __( 'Please enter your Rakuten Pay Signature key. This is needed to process the payment. Is possible get your Signature Key in %s.', 'woocommerce-rakuten-pay' ), '<a href="https://dashboard.rakutenpay.com.br/">' . __( 'Rakuten Pay Dashboard > My Account page', 'woocommerce-rakuten-pay' ) . '</a>' ),
        'default'           => '',
        'custom_attributes' => array(
          'required' => 'required',
        ),
      ),
			'environment' => array(
				'title'       => __( 'Environment', 'woocommerce-rakuten-pay' ),
				'type'        => 'select',
				'description' => sprintf( __( 'Rakuten Pay has two environemnts, th e Sandbox used to make test transactions, and Production used for real transactions.', 'woocommerce-rakuten-pay' ) ),
				'default'     => 'production',
				'options'     => array(
					'production'  => sprintf( __( 'Production', 'woocommerce-raktuten-pay' ) ),
					'sandbox'     => sprintf( __( 'Sandbox', 'woocommerce-raktuten-pay' ) )
				)
			),
      'installments' => array(
        'title'       => __( 'Installments', 'woocommerce-rakuten-pay' ),
        'type'        => 'title',
        'description' => '',
      ),
      'max_installment' => array(
        'title'       => __( 'Number of Installment', 'woocommerce-rakuten-pay' ),
        'type'        => 'select',
        'class'       => 'wc-enhanced-select',
        'default'     => '12',
        'description' => __( 'Maximum number of installments possible with payments by credit card.', 'woocommerce-rakuten-pay' ),
        'desc_tip'    => true,
        'options'     => array(
          '1'  => '1',
          '2'  => '2',
          '3'  => '3',
          '4'  => '4',
          '5'  => '5',
          '6'  => '6',
          '7'  => '7',
          '8'  => '8',
          '9'  => '9',
          '10' => '10',
          '11' => '11',
          '12' => '12',
        ),
      ),
      'smallest_installment' => array(
        'title'       => __( 'Smallest Installment', 'woocommerce-rakuten-pay' ),
        'type'        => 'text',
        'description' => __( 'Please enter with the value of smallest installment, Note: it not can be less than 5.', 'woocommerce-rakuten-pay' ),
        'desc_tip'    => true,
        'default'     => '5',
      ),
      'buyer_interest_conf' => array(
        'title'       => __( 'Buyer Interest Configuration', 'woocommerce-rakuten-pay' ),
        'type'        => 'title',
        'description' => '',
      ),
      'buyer_interest' => array(
        'title'       => __( 'Buyer Interest', 'woocommerce-rakuten-pay' ),
        'type'        => 'select',
        'description' => __( 'Enables the display of the parcel listing on the product preview screen. (You will see the largest installments available for the product in payment by credit card)', 'woocommerce-rakuten-pay' ),
        'default'     => 'no',
        'options'     => array(
          'no'        => __( 'No', 'woocommerce-raktuten-pay'),
          'yes'       => __( 'Yes', 'woocommerce-raktuten-pay' ),
        ),
      ),
      'free_installments' => array(
        'title'       => __( 'Free Installments', 'woocommerce-rakuten-pay' ),
        'type'        => 'select',
        'class'       => 'wc-enhanced-select',
        'default'     => '1',
        'description' => __( 'Number of installments with interest free.', 'woocommerce-rakuten-pay' ),
        'desc_tip'    => true,
        'options'     => array(
          '0'  => _x( 'None', 'no free installments', 'woocommerce-rakuten-pay' ),
          '1'  => '1',
          '2'  => '2',
          '3'  => '3',
          '4'  => '4',
          '5'  => '5',
          '6'  => '6',
          '7'  => '7',
          '8'  => '8',
          '9'  => '9',
          '10' => '10',
          '11' => '11',
          '12' => '12',
        ),
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
        'description' => sprintf( __( 'Log Rakuten Pay events, such as API requests. You can check the log in %s', 'woocommerce-rakuten-pay' ), '<a href="' . esc_url( admin_url( 'admin.php?page=wc-status&tab=logs&log_file=' . esc_attr( $this->id ) . '-' . sanitize_file_name( wp_hash( $this->id ) ) . '.log' ) ) . '">' . __( 'System Status &gt; Logs', 'woocommerce-rakuten-pay' ) . '</a>' ),
      ),
    );
  }

  /**
   * Checkout scripts.
   */
  public function checkout_scripts() {
    if ( is_checkout() ) {
      $suffix = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? '' : '.min';

      wp_enqueue_script( 'wc-credit-card-form' );
      wp_enqueue_script( 'rakuten-pay-library', $this->api->get_js_url(), array( 'jquery' ), null );
      wp_enqueue_script( 'jquery-inputmask', plugins_url( 'assets/js/jquery.inputmask.min' . '.js', plugin_dir_path( __FILE__ ) ), array ( 'jquery' ), null );
      wp_enqueue_script( 'rakuten-pay-credit-card', plugins_url( 'assets/js/credit-card' . '.js', plugin_dir_path( __FILE__ ) ), array( 'jquery', 'jquery-blockui', 'jquery-inputmask', 'rakuten-pay-library' ), WC_Rakuten_Pay::VERSION, true );

      wp_localize_script(
        'rakuten-pay-credit-card',
        'wcRakutenPayParams',
        array(
          'signatureKey'    => $this->signature_key,
          'error_message'         => __( 'PayVault tokenization error response', 'woocommerce-rakuten-pay' ),
        )
      );
    }
  }

  /**
   * Payment fields.
   */
  public function payment_fields() {

    $buyer_interest = $this->api->get_buyer_interest();
    $installments = $this->api->get_installments_buyer_interest();

    if ( $buyer_interest == 'yes' ) {

        if ( $description = $this->get_description() ) {
            echo wp_kses_post( wpautop( wptexturize( $description ) ) );
        }

        $amount = (float) $this->get_order_total();

        $installments = $this->api->get_installments( $amount );
        $installments = $this->apply_free_installments( $installments );

        wc_get_template(
            'credit-card/payment-form.php',
            array(
                'max_installment'      => $this->max_installment,
                'smallest_installment' => $this->api->get_smallest_installment(),
                'installments'         => $installments,
                'buyer_interest'       => $buyer_interest,
            ),
            'woocommerce/woocommerce-rakuten-pay/',
            WC_Rakuten_Pay::get_templates_path()
        );

    } else {
        $buyer_interest = 'no';
        wc_get_template(
            'credit-card/payment-form.php',
            array(
                'max_installment'      => $this->max_installment,
                'smallest_installment' => $this->api->get_smallest_installment(),
                'installments'         => $installments,
                'buyer_interest'       => $buyer_interest,
            ),
            'woocommerce/woocommerce-rakuten-pay/',
            WC_Rakuten_Pay::get_templates_path()
        );
    }
  }

  /**
   * Apply Free Installments
   * @param  array $installments Installments from get_installments.
   * @return array $result       Installments with free installments applied.
   */
  private function apply_free_installments( $installments ) {
    return array_map( function( $inst ) { 
      if ( $inst['quantity'] > $this->free_installments ) {
        return $inst;
      }

      $inst['interest_percent'] = 0.0;
      $inst['amount'] = 0.0;
      $inst['total'] = $inst['total'] - $inst['interest_amount'];
      $inst['interest_amount'] = 0.0;
      $inst['installment_amount'] = $inst['total'] / $inst['quantity'];
      return $inst;
    }, $installments );
  }

  /**
   * Refund banking data
   */
  public function refund_banking_data_fields( $order ) {
    if ( 'rakuten-pay-credit-card' !== $order->get_payment_method() ) {
      return;
    }

    wc_get_template(
      'credit-card/refund-banking-data.php',
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

    if ( isset( $data['installments'] ) && in_array( $order->get_status(), array( 'processing', 'on-hold' ), true ) ) {
      wc_get_template(
        'credit-card/payment-instructions.php',
        array(
          'card_brand'   => $data['card_brand'],
          'installments' => $data['installments'],
        ),
        'woocommerce/rakuten-pay/',
        WC_Rakuten_Pay::get_templates_path()
      );
    }
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

    if ( isset( $data['installments'] ) ) {
      $email_type = $plain_text ? 'plain' : 'html';

      wc_get_template(
        'credit-card/emails/' . $email_type . '-instructions.php',
        array(
          'card_brand'   => $data['card_brand'],
          'installments' => $data['installments'],
        ),
        'woocommerce/rakuten-pay/',
        WC_Rakuten_Pay::get_templates_path()
      );
    }
  }

  /**
   * Credit Card handler.
   * Delegates all calls to api IPN handler.
   */
  public function credit_card_handler() {
    $this->api->ipn_handler();
  }

  public function custom_checkout_fields( $fields ) {
    $fields = $this->custom_billing_checkout_fields( $fields );
    $fields = $this->custom_shipping_checkout_fields( $fields );
    $fields = $this->reorder_all_fields( $fields );
    return $fields;
  }

  private function custom_billing_checkout_fields( $fields ) {
    $billing_fields = $fields['billing'];

    $billing_fields = array_merge(
      $billing_fields,
      array(
        'billing_birthdate' => array(
          'label'           => __( 'Birthdate', 'woocommerce-rakuten-pay' ),
          'placeholder'     => __( 'Data de nascimento', 'placeholder', 'woocommerce-rakuten-pay' ),
          'required'        => true,
          'foo-bar-baz'     => 'hei-how',
          'class'           => array( 'form-row-wide' ),
          'clear'           => true
        ),
        'billing_document'  => array(
          'label'           => __( 'Document', 'woocommerce-rakuten-pay' ),
          'placeholder'     => __( 'Informe seu CPF', 'placeholder', 'woocommerce-rakuten-pay' ),
          'required'        => true,
          'class'           => array( 'form-row-wide' ),
          'clear'           => true
        ),
        'billing_address_number'    => array(
          'label'           => __( 'Number', 'woocommerce-rakuten-pay' ),
          'placeholder'     => __( 'Number', 'placeholder', 'woocommerce-rakuten-pay' ),
          'required'        => true,
          'class'           => array( 'form-row-wide' ),
          'clear'           => true
        ),
        'billing_district'  => array(
          'label'           => __( 'District', 'woocommerce-rakuten-pay' ),
          'placeholder'     => __( 'Bairro', 'placeholder', 'woocommerce-rakuten-pay' ),
          'required'        => true,
          'class'           => array( 'form-row-wide' ),
          'clear'           => true
        )
      )
    );

    $billing_fields['billing_birthdate']['priority'] = 40;
    $billing_fields['billing_phone']['priority'] = 50;
    $billing_fields['billing_email']['priority'] = 50;
    $billing_fields['billing_document']['priority'] = 60;
    $billing_fields['billing_address_number']['priority'] = 80;
    $billing_fields['billing_district']['priority'] = 100;

    $billing_fields['billing_phone']['required'] = true;

    $fields['billing'] = $billing_fields;

    return $fields;
  }

  private function custom_shipping_checkout_fields( $fields ) {
    $shipping_fields = $fields['shipping'];

    $shipping_fields = array_merge(
      $shipping_fields,
      array(
        'shipping_address_number'    => array(
          'label'           => __( 'Number', 'woocommerce-rakuten-pay' ),
          'placeholder'     => __( 'Number', 'placeholder', 'woocommerce-rakuten-pay' ),
          'required'        => true,
          'class'           => array( 'form-row-first' ),
          'clear'           => true
        ),
        'shipping_district'    => array(
          'label'           => __( 'District', 'woocommerce-rakuten-pay' ),
          'placeholder'     => __( 'District', 'placeholder', 'woocommerce-rakuten-pay' ),
          'required'        => true,
          'class'           => array( 'form-row-wide' ),
          'clear'           => true
        ),
        'shipping_phone_number'    => array(
          'label'           => __( 'Phone number' , 'woocommerce-rakuten-pay' ),
          'placeholder'     => __( 'Número de telefone' , 'placeholder', 'woocommerce-rakuten-pay' ),
          'required'        => true,
          'class'           => array( 'form-row-wide' ),
          'clear'           => true
        ),
      )
    );

    $shipping_fields['shipping_address_number']['priority'] = 100;
    $shipping_fields['shipping_district']['priority'] = 110;
    $shipping_fields['shipping_phone_number']['priority'] = 55;

    $fields['shipping'] = $shipping_fields;

    return $fields;
  }

  public function reorder_custom_default_address_fields( $fields ) {
      $fields['postcode']['priority'] = 60;
      $fields['address_1']['priority'] = 70;
      $fields['address_2']['priority'] = 90;
      $fields['city']['priority'] = 110;
      $fields['state']['priority'] = 120;
      $fields['country']['priority'] = 130;
      $fields['state']['required'] = true;

      return $fields;
  }

  public function reorder_all_fields( array $fields ) {
    // Billing fields

      $fields['billing']['billing_company']['priority'] = 10;
      $fields['billing']['billing_first_name']['priority'] = 20;
      $fields['billing']['billing_last_name']['priority'] = 25;
      $fields['billing']['billing_document']['priority'] = 30;

    // Shipping Fields
    $fields['shipping']['shipping_address_1']['priority'] = 70;
    $fields['shipping']['shipping_address_2']['priority'] = 90;
    $fields['shipping']['shipping_address_number']['priority'] = 100;
    $fields['shipping_phone_number']['priority'] = 105;
    $fields['shipping']['shipping_district']['priority'] = 110;
    $fields['shipping']['shipping_postcode']['priority'] = 120;
    $fields['shipping']['shipping_city']['priority'] = 130;
    $fields['shipping']['shipping_state']['priority'] = 140;
    $fields['shipping']['shipping_country']['priority'] = 150;

    return $fields;
  }

// TODO verficar método
//  public function checkout_shipping_document_fields( $fields ) {
//    $shipping_fields = $fields['shipping'];
//
//    $new_shipping_fields = array(
//      $this->array_slice( $shipping_fields,
//        array(
//          'shipping_first_name', 'shipping_last_name', 'shipping_company'
//        )
//      ) +
//      array(
//        'shipping_birthdate' => array(
//          'label'           => __( 'Birthdate', 'woocommerce' ),
//          'placeholder'     => __( 'Birthdate', 'placeholder', 'woocommerce' ),
//          'required'        => true,
//          'foo-bar-baz'     => 'hei-how',
//          'class'           => array( 'form-row-wide' ),
//          'clear'           => true
//        ),
//      ) +
//      $this->array_slice(
//        $shipping_fields, array( 'shipping_company' )
//      ) +
//      $this->array_slice(
//        $shipping_fields,
//        array(
//          'shipping_address_1', 'shipping_address_2'
//        )
//      ) +
//      array(
//        'shipping_address_number'    => array(
//          'label'           => __( 'Number', 'woocommerce-rakuten-log' ),
//          'placeholder'     => __( 'Number', 'placeholder', 'woocommerce' ),
//          'required'        => true,
//          'class'           => array( 'form-row-wide' ),
//          'clear'           => true
//        ),
//        'shipping_district'  => array(
//          'label'           => __( 'District', 'woocommerce-rakuten-log' ),
//          'placeholder'     => __( 'District', 'placeholder', 'woocommerce-rakuten-log' ),
//          'required'        => true,
//          'class'           => array( 'form-row-wide' ),
//          'clear'           => true
//        )
//      ) +
//      $this->array_slice(
//        $shipping_fields,
//        array(
//          'shipping_city',
//          'shipping_postcode',
//          'shipping_country',
//          'shipping_state',
//          'shipping_email',
//          'shipping_phone'
//        )
//      ),
//    );
//
//    $fields['shipping'] = $new_shipping_fields;
//
//    return $fields;
//  }

  /**
   * Cancels Credit Card transaction.
   *
   * @param int $order_id Order ID.
   */
  public function cancel_credit_card_transaction( $order_id ) {
    $order = wc_get_order( $order_id );

    if ( $this->api->is_credit_card_payment_method( $order ) ) {
      $order->add_order_note(
        __( 'Rakuten Pay: Credit Card Orders cannot be cancelled, you must wait the approve to proceed the refund.', 'woocommerce-rakuten-pay' )
      );
      return;
    }
  }

  private function array_slice( $arr, $keys ) {
    wp_array_slice_assoc( $arr, $keys );
  }

    /**
     * Pre populate already filled billing or shipping fields
     *
     * @param $key
     * @return mixed
     *
     */
  public function rk_populate_checkout_fields( $key ) {
      global $current_user;
      $current_user = wp_get_current_user();
      $current_user_id = $current_user->ID;
      $document = get_user_meta( $current_user_id, '', true );

      if ( isset($document['billing_document'][0]) ) {
          switch ($key) :
              case 'billing_first_name':
              case 'shipping_first_name':
                  return $current_user->first_name;
                  break;

              case 'billing_last_name':
              case 'shipping_last_name':
                  return $current_user->last_name;
                  break;
              case 'billing_email':
                  return $current_user->user_email;
                  break;
              case 'billing_document':
                  return $document['billing_document'][0];
                  break;
              case 'billing_birthdate':
                  return $document['billing_birthdate'][0];
                  break;
          endswitch;
      }
  }
}
