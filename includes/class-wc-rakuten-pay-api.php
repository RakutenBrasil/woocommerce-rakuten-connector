<?php
/**
 * Rakuten Pay API
 *
 * @package WooCommerce_Rakuten_Pay/API
 */

if ( ! defined( 'ABSPATH' ) ) {
  exit;
}

/**
 * WC_Rakuten_Pay_API class.
 */
class WC_Rakuten_Pay_API {

  /**
   * PRODUCTION API URL.
   */
  const PRODUCTION_API_URL = 'https://api.rakuten.com.br/rpay/v1/';

  /**
   * SANDBOX API URL.
   */
   const SANDBOX_API_URL = 'http://oneapi-sandbox.rakutenpay.com.br/rpay/v1/';

  /**
   * Gateway class.
   *
   * @var WC_Rakuten_Pay_Gateway
   */
  protected $gateway;

  /**
   * JS Library URL.
   *
   * @var string
   */
  protected $js_url = 'https://static.rakutenpay.com.br/rpayjs/rpay-latest.dev.min.js';

  /**
   * Constructor.
   *
   * @param WC_Payment_Gateway $gateway Gateway instance.
   */
  public function __construct( $gateway = null ) {
    $this->gateway = $gateway;
  }

  /**
   * Get API URL.
   *
   * @return string
   */
  public function get_api_url() {
    if ( 'production' === $this->gateway->environment ) {
      return self::PRODUCTION_API_URL;
    } else {
      return self::SANDBOX_API_URL;
    }
  }

  /**
   * Get JS Library URL.
   *
   * @return string
   */
  public function get_js_url() {
    return $this->js_url;
  }

  /**
   * Returns a bool that indicates if currency is amongst the supported ones.
   *
   * @return bool
   */
  public function using_supported_currency() {
    return 'BRL' === get_woocommerce_currency();
  }

  /**
   * Only numbers.
   *
   * @param  string|int $string String to convert.
   *
   * @return string|int
   */
  protected function only_numbers( $string ) {
    return preg_replace( '([^0-9])', '', $string );
  }

  /**
   * Get the smallest installment amount.
   *
   * @return int
   */
  public function get_smallest_installment() {
    return ( 5 > $this->gateway->smallest_installment ) ? 500 : wc_format_decimal( $this->gateway->smallest_installment );
  }

  /**
   * Do POST requests in the Rakuten Pay API.
   *
   * @param  string $endpoint API Endpoint.
   * @param  array  $data     Request data.
   * @param  array  $headers  Request headers.
   *
   * @return array            Request response.
   */
  protected function do_post_request( $endpoint, $data = array(), $headers = array() ) {
    $params = array(
      'timeout' => 60,
      'method' => 'POST'
    );

    if ( ! empty( $data ) ) {
      $params['body'] = $data;
    }

    if ( ! empty( $headers ) ) {
      $params['headers'] = $headers;
    }

    return wp_remote_post( $this->get_api_url() . $endpoint, $params );
  }

  /**
   * Do GET requests in the Rakuten Pay API.
   *
   * @param  string $endpoint API Endpoint.
   * @param  array  $headers  Request headers.
   *
   * @return array            Request response.
   */
  protected function do_get_request( $endpoint, $headers = array() ) {
    $params = array(
      'timeout' => 60,
      'method'  => 'GET'
    );

    if ( ! empty( $headers ) ) {
      $params['headers'] = $headers;
    }

    return wp_remote_get( $this->get_api_url() . $endpoint, $params );
  }

  /**
   * Generate the charge data.
   *
   * @param  WC_Order $order           Order data.
   * @param  string   $payment_method  Payment method.
   * @param  array    $posted          Form posted data.
   * @param  array    $installment     In case of not free installment
   *
   * @return array            Charge data.
   */
  public function generate_charge_data( $order, $payment_method, $posted, $installments ) {
    $customer_name  = trim( $order->get_billing_first_name() . ' ' . $order->get_billing_last_name() );

    // Root
    $data = array(
      'reference'   => $order->get_order_number(),
      'amount'      => (float) $order->get_total(),
      'currency'    => get_woocommerce_currency(),
      'webhook_url' => WC()->api_request_url( get_class( $this->gateway ) ),
      'fingerprint' => $posted['rakuten_pay_fingerprint'],
      'payments'    => array(),
      'customer'    => array(
        'document'      => $this->only_numbers($posted['billing_document']),
        'name'          => $customer_name,
        'business_name' => $posted['billing_company'] ?: $customer_name,
        'email'         => $order->get_billing_email(),
        'birth_date'    => preg_replace(
          '/(\d{2})\/(\d{2})\/(\d{4})/',
          '${3}-${2}-${1}',
          $posted['billing_birthdate']
        ),
        'gender'        => $posted['billing_gender'],
        'kind'          => 'personal',
        'addresses'     => array(),
        'phones'        => array(
          array(
            'kind'         => 'billing',
            'reference'    => 'others',
            'number'       => array(
              'country_code' => '55',
              'area_code'    => preg_replace(
                '/\((\d{2})\)\s(\d{4,5})-(\d{4})/',
                '${1}',
                $order->get_billing_phone()
              ),
              'number' => preg_replace(
                '/\((\d{2})\)\s(\d{4,5})-(\d{4})/',
                '${2}${3}',
                $order->get_billing_phone()
              )
            )
          ),
          array(
            'kind'         => 'shipping',
            'reference'    => 'others',
            'number'       => array(
              'country_code' => '55',
              'area_code'    => preg_replace(
                '/\((\d{2})\)\s(\d{4,5})-(\d{4})/',
                '${1}',
                $order->get_billing_phone()
              ),
              'number' => preg_replace(
                '/\((\d{2})\)\s(\d{4,5})-(\d{4})/',
                '${2}${3}',
                $order->get_billing_phone()
              )
            )
          )
        )
      ),
      'order' => array(
        'reference'       => (string) $order->get_id(),
        'payer_ip'        => $this->customer_ip_address( $order ),
        'items_amount'    => (float) $order->get_subtotal(),
        'shipping_amount' => (float) $order->get_shipping_total(),
        'taxes_amount'    => (float) $order->get_total_tax(),
        'discount_amount' => (float) $order->get_total_discount(),
        'items' => array_map(
          function( $item ) {
            $product = $item->get_product();
            return array(
              'reference'     => $product->get_sku(),
              'description'   => substr( $product->get_description(), 0, 255 ),
              'amount'        => (float) $product->get_price(),
              'quantity'      => $item->get_quantity(),
              'total_amount'  => (float) $item->get_total(),
              'categories'    => array_map(
                function( $term ) {
                  return array(
                    'id'   => (string) $term->term_id,
                    'name' => $term->name
                  );
                }, wp_get_post_terms( $product->get_id(), 'product_cat' )
              )
            );
          }, array_values( $order->get_items() )
        )
      )
    );

    //Billing Address.
    if ( ! empty( $order->get_billing_address_1() ) ) {
      $billing_address = array(
        'kind'          => 'billing',
        'contact'       => $customer_name,
        'street'        => $order->get_billing_address_1(),
        'complement'    => $order->get_billing_address_2(),
        'city'          => $order->get_billing_city(),
        'state'         => $order->get_billing_state(),
        'country'       => $order->get_billing_country(),
        'zipcode'       => $this->only_numbers( $order->get_billing_postcode() ),
      );

      // Non-WooCommerce default address fields.
      if ( ! empty( $posted['billing_number'] ) ) {
        $billing_address['number'] = $posted['billing_number'];
      }
      if ( ! empty( $posted['billing_district'] ) ) {
        $billing_address['district'] = $posted['billing_district'];
      }

      $data['customer']['addresses'][] = $billing_address;
    }

    if ( $payment_method == 'credit_card' ) {
      $payment = array(
        'reference'                => '1',
        'method'                   => $payment_method,
        'amount'                   => (float) $order->get_total(),
        'installments_quantity'    => (integer) $posted['rakuten_pay_installments'],
        'brand'                    => strtolower( $posted['rakuten_pay_card_brand'] ),
        'token'                    => $posted['rakuten_pay_token'],
        'cvv'                      => $posted['rakuten_pay_card_cvc'],
        'holder_name'              => $posted['rakuten_pay_card_holder_name'],
        'holder_document'          => $posted['rakuten_pay_card_holder_document'],
        'options'                  => array(
          'save_card'   => true,
          'new_card'    => true,
          'recurrency'  => false
        )
      );
      if ( isset( $installments ) ) {
        $payment['installments'] = $installments;
      }
    } else {
      $payment = array(
        'method'     => $payment_method,
        'expires_on' => date( 'Y-m-d', $this->strtotime( '+3 day' ) ),
        'amount'     => (float) $order->get_total()
      );
    }

    $data['payments'][] = $payment;

    // Shipping Address
    if ( ! empty( $posted['ship_to_different_address'] ) ) {
      $shipping_address = array(
        'kind'       => 'shipping',
        'contact'    => $customer_name,
        'street'     => $order->get_shipping_address_1(),
        'complement' => $order->get_shipping_address_2(),
        'zipcode'    => $this->only_numbers( $order->get_shipping_postcode() ),
        'city'       => $order->get_shipping_city(),
        'state'      => $order->get_shipping_state(),
        'country'    => $order->get_shipping_country()
      );

      // Non-WooCommerce default address fields.
      if ( ! empty( $posted['shipping_number'] ) ) {
        $shipping_address['number'] = $posted['shipping_number'];
      }
      if ( ! empty( $posted['shipping_district'] ) ) {
        $shipping_address['district'] = $posted['shipping_district'];
      }

      $data['customer']['addresses'][] = $shipping_address;
    } else {
      $shipping_address                = $billing_address;
      $shipping_address['kind']        = 'shipping';
      $data['customer']['addresses'][] = $shipping_address;
    }

    return $data;
  }

  /**
   * Generate the charge data.
   *
   * @param  WC_Order $order             Order data.
   * @param  array    $posted            Form posted data.
   * @param  array    $transaction_data  Transaction data.
   *
   * @return array                       [kind: total|partial, Charge data].
   */
  public function generate_refund_data( $order, $payment_method, $posted, $transaction_data ) {
    $customer_name  = trim( $order->get_billing_first_name() . ' ' . $order->get_billing_last_name() );
    $transaction_id = get_post_meta( $order->get_id(), '_wc_rakuten_pay_transaction_id', true );
    $refund_reason  = $posted['refund_reason'];
    $paid_value     = (float) $order->get_total();
    $refund_value   = (float) $posted['refund_amount'];

    if ( $paid_value === $refund_value ) {
      $kind = 'total';
    } else {
      $kind = 'partial';
    }

    // Root
    $data = array(
      'requesters'  => 'merchant',
      'reason'      => $refund_reason,
      'amount'      => $refund_value,
      'payments'    => array(
        array(
          'id'     => $transaction_data['payments'][0]['id'],
          'amount' => (float) $refund_value
        )
      )
    );

    // Billet refund data
    if ( 'billet' === $payment_method ) {
      $banking_account_data = array(
        'document'    => $this->only_numbers( $posted['refund_customer_document'] ),
        'bank_code'   => $this->only_numbers( $posted['refund_bank_code'] ),
        'bank_agency' => $this->only_numbers( $posted['refund_bank_agency'] ),
        'bank_number' => $posted['refund_bank_number']
      );

      $data['payments'][0]['bank_account'] = $banking_account_data;
    }

    return [$kind, $data];
  }

  /**
   * Do the charge transaction.
   *
   * @param  WC_Order $order Order data.
   * @param  array    $args  Transaction args.
   *
   * @return array Response data.
   *   array( 'result' => 'fail' ) for general request failures
   *   array( 'result' => 'failure', 'errors' => errors[] ) for Rakuten Pay errors
   *   array( 'result' => 'authorized', ... ) for authorized Rakuten Pay transactions
   */
  public function charge_transaction( $order, $charge_data) {
    if ( 'yes' === $this->gateway->debug ) {
      $this->gateway->log->add( $this->gateway->id, 'Doing a charge charge_transaction for order ' . $order->get_order_number() . '...' );
    }

    $endpoint = 'charges';
    $body     = json_encode( $charge_data, JSON_PRESERVE_ZERO_FRACTION );
    $headers  = array(
      'Authorization' => $this->authorization_header(),
      'Signature' => $this->get_signature( $body ),
      'Content-Type' => 'application/json'
    );
    $response = $this->do_post_request( $endpoint, $body, $headers );

    if ( is_wp_error( $response ) ) {
      if ( 'yes' === $this->gateway->debug ) {
        $this->gateway->log->add( $this->gateway->id, 'WP_Error in doing the charge_transaction: ' . $response->get_error_message() );
      }
      return array( 'result' => 'fail' );
    }

    $response_body = json_decode( $response['body'], true );

    if ( $response['response']['code'] != 200 ) {
      $error_message = '';
      if ( isset( $response_body['errors'] ) ) {
        foreach ( $response_body['errors'] as $error ) {
          $error_message .= $error['description'] . '\n';
        }
      }
      if ( 'yes' === $this->gateway->debug ) {
        $this->gateway->log->add( $this->gateway->id, 'Fail in doing the charge_transaction: ' . $error_message );
      }
      return array( 'result' => 'fail' );
    }

    if ( $response_body['result'] == 'failure' ) {
      if ( 'yes' === $this->gateway->debug ) {
        $this->gateway->log->add( $this->gateway->id, 'Failed to make the transaction: ' . print_r( $response, true ) );
      }
      return $response_body;
    }

    if ( 'yes' === $this->gateway->debug ) {
      $this->gateway->log->add( $this->gateway->id, 'Transaction completed successfully! The charge_transaction response is: ' . print_r( $response_body, true ) );
    }
    return $response_body;
  }

  /**
   * Cancels the transaction.
   *
   * @param  WC_Order $order Order data.
   * @param  string   $token Checkout token.
   *
   * @return array           Response data.
   */
  public function cancel_transaction( $order ) {
    if ( 'yes' === $this->gateway->debug ) {
      $this->gateway->log->add( $this->gateway->id, 'Cancelling payment for order ' . $order->get_order_number() . '...' );
    }

    $body           = json_encode( array(), JSON_PRESERVE_ZERO_FRACTION );
    $transaction_id = get_post_meta( $order->get_id(), '_wc_rakuten_pay_transaction_id', true );
    $headers        = array(
      'Authorization' => $this->authorization_header(),
      'Signature'     => $this->get_signature( $body ),
      'Content-Type' => 'application/json'
    );
    $endpoint       = 'charges/' . $transaction_id . '/cancel';
    $response       = $this->do_post_request( $endpoint, $body, $headers );

    if ( is_wp_error( $response ) ) {
      if ( 'yes' === $this->gateway->debug ) {
        $this->gateway->log->add( $this->gateway->id, 'WP_Error in doing the transaction: ' . $response->get_error_message() );
      }
      $transaction_url = '<a href="https://dashboard.rakuten.com.br/sales/' . intval( $transaction_id ) . '">https://dashboard.rakuten.com.br/sales/' . intval( $transaction_id ) . '</a>';
      $this->send_email(
        sprintf( esc_html__( 'The cancel transaction for order %s has failed.', 'woocommerce-rakuten-pay' ), $order->get_order_number() ),
        esc_html__( 'Transaction failed', 'woocommerce-rakuten-pay' ),
        sprintf( esc_html__( 'In order to cancel this transaction access the rakuten pay dashboard:  %1$s.', 'woocommerce-rakuten-pay' ), $transaction_url )
      );
      $order->add_order_note( __('Rakuten Pay: Order could not be cancelled due to an error. You must access the Rakuten Pay Dashboard to complete the cancel operation', 'woocommerce-rakuten-pay' ) );
      return;
    }

    $data = json_decode( $response['body'], true );

    if ( $data['result'] == 'failure' ) {
      if ( 'yes' === $this->gateway->debug ) {
        $this->gateway->log->add( $this->gateway->id, 'Failed to make the transaction: ' . print_r( $response, true ) );
      }
      return;
    }

    if ( 'yes' === $this->gateway->debug ) {
      $this->gateway->log->add( $this->gateway->id, 'Transaction completed successfully! The transaction response is: ' . print_r( $data, true ) );
    }

    update_post_meta( $order->get_id(), '_wc_rakuten_pay_order_cancelled', 'yes' );

    return;
  }

  /**
   * Do the refund transaction.
   *
   * @param  WC_Order $order        Order data.
   * @param  array    $refund_data  Refund transaction args.
   *
   * @return array Response data.
   *   array( 'result' => 'fail' ) for general request failures
   *   array( 'result' => 'failure', 'errors' => errors[] ) for Rakuten Pay errors
   *   array( 'result' => 'authorized', ... ) for authorized Rakuten Pay transactions
   */
  public function refund_transaction( $order, $refund_kind, $refund_data ) {
    $body           = json_encode( $refund_data, JSON_PRESERVE_ZERO_FRACTION );
    $transaction_id = get_post_meta( $order->get_id(), '_wc_rakuten_pay_transaction_id', true );
    $headers        = array(
      'Authorization' => $this->authorization_header(),
      'Signature'     => $this->get_signature( $body ),
      'Content-Type' => 'application/json'
    );
    if ( 'total' === $refund_kind ) {
      $refund_route = '/refund';
    } else {
      $refund_route = '/refund_partial';
    }
    $endpoint       = 'charges/' . $transaction_id . $refund_route;
    $response       = $this->do_post_request( $endpoint, $body, $headers );

    if ( is_wp_error( $response ) ) {
      if ( 'yes' === $this->gateway->debug ) {
        $this->gateway->log->add( $this->gateway->id, 'WP_Error in doing the refund_transaction: ' . $response->get_error_message() );
      }
      $transaction_url = '<a href="https://dashboard.rakuten.com.br/sales/' . intval( $transaction_id ) . '">https://dashboard.rakuten.com.br/sales/' . intval( $transaction_id ) . '</a>';
      $this->send_email(
        sprintf( esc_html__( 'The refund transaction for order %s has failed.', 'woocommerce-rakuten-pay' ), $order->get_order_number() ),
        esc_html__( 'Transaction failed', 'woocommerce-rakuten-pay' ),
        sprintf( esc_html__( 'In order to refund this transaction access the rakuten pay dashboard:  %1$s.', 'woocommerce-rakuten-pay' ), $transaction_url )
      );
      $order->add_order_note( __('Rakuten Pay: Order could not be refunded due to an error. You must access the Rakuten Pay Dashboard to complete the cancel operation', 'woocommerce-rakuten-pay' ) );
      return false;
    }

    $response_body = json_decode( $response['body'], true );

    if ( $response['response']['code'] != 200 ) {
      $error_message = '';
      if ( isset( $response_body['errors'] ) ) {
        foreach ( $response_body['errors'] as $error ) {
          $error_message .= $error['description'] . '\n';
        }
      }
      if ( 'yes' === $this->gateway->debug ) {
        $this->gateway->log->add( $this->gateway->id, 'Fail in doing the refund_transaction: \n' . $error_message );
      }
      return false;
    }

    if ( $response_body['result'] == 'failure' ) {
      if ( 'yes' === $this->gateway->debug ) {
        $this->gateway->log->add( $this->gateway->id, 'Failed to make the refund_transaction: ' . print_r( $response, true ) );
      }
      return false;
    }

    if ( 'yes' === $this->gateway->debug ) {
      $this->gateway->log->add( $this->gateway->id, 'Transaction completed successfully! The refund_transaction response is: ' . print_r( $response_body, true ) );
    }

    $refunded_ids   = get_post_meta( $order->get_id(), '_wc_rakuten_pay_order_refunded_ids', true );
    $refunded_ids   = $refunded_ids ?: array();
    $refund_id      = $response_body['refunds'][0]['id'];
    $refunded_ids[] = $refund_id;

    update_post_meta( $order->get_id(), '_wc_rakuten_pay_order_refunded_ids', $refunded_ids );
    return true;
  }

  /**
   * Get transaction data.
   *
   * @param  WC_Order $order        Order data.
   *
   * @return array Response data.
   *   false for general request failures
   *   array( 'result' => 'data', ... ) with data from Rakuten Pay transaction
   */
  public function get_transaction( $order ) {
    $transaction_id = get_post_meta( $order->get_id(), '_wc_rakuten_pay_transaction_id', true );
    $headers        = array(
      'Authorization' => $this->authorization_header(),
      'Content-Type' => 'application/json'
    );
    $endpoint       = 'charges/' . $transaction_id;
    $response       = $this->do_get_request( $endpoint, $headers );

    if ( is_wp_error( $response ) ) {
      if ( 'yes' === $this->gateway->debug ) {
        $this->gateway->log->add( $this->gateway->id, 'WP_Error in doing the get_transaction: ' . $response->get_error_message() );
      }
      return false;
    }

    $response_body = json_decode( $response['body'], true );

    if ( $response['response']['code'] != 200 ) {
      $error_message = '';
      if ( isset( $response_body['errors'] ) ) {
        foreach ( $response_body['errors'] as $error ) {
          $error_message .= $error['description'] . '\n';
        }
      }
      if ( 'yes' === $this->gateway->debug ) {
        $this->gateway->log->add( $this->gateway->id, 'Fail in doing the get_transaction: \n' . $error_message );
      }
      return false;
    }

    if ( 'yes' === $this->gateway->debug ) {
        $this->gateway->log->add( $this->gateway->id, 'Failed to make the get_transaction: ' . print_r( $response, true ) );
    }

    return $response_body;
  }

  /**
   * Get installments
   *
   * @param  float           $amount   Amount
   * @return array | false   $result   Installments or false for errors
   */
  public function get_installments( $amount ) {
    $headers        = array(
      'Authorization' => $this->authorization_header(),
      'Content-Type' => 'application/json'
    );
    $endpoint       = add_query_arg( array(
      'amount' => $amount
    ), 'checkout' );
    $response       = $this->do_get_request( $endpoint, $headers );

    if ( is_wp_error( $response ) ) {
      if ( 'yes' === $this->gateway->debug ) {
          $this->gateway->log->add( $this->gateway->id, 'WP_Error in doing the get_installments: ' . $response->get_error_message() );
      }
      return false;
    }

    $response_body = json_decode( $response['body'], true );
    $installments  = array_filter( $response_body['payments'], function( $p ) {
      return $p['method'] == 'credit_card';
    } );
    return $installments[0]['installments'];
  }

  /**
   * Get card brand name.
   *
   * @param string $brand Card brand.
   * @return string
   */
  protected function get_card_brand_name( $brand ) {
    $names = array(
      'visa'       => __( 'Visa', 'woocommerce-rakuten-pay' ),
      'mastercard' => __( 'MasterCard', 'woocommerce-rakuten-pay' ),
      'amex'       => __( 'American Express', 'woocommerce-rakuten-pay' ),
      'aura'       => __( 'Aura', 'woocommerce-rakuten-pay' ),
      'jcb'        => __( 'JCB', 'woocommerce-rakuten-pay' ),
      'diners'     => __( 'Diners', 'woocommerce-rakuten-pay' ),
      'elo'        => __( 'Elo', 'woocommerce-rakuten-pay' ),
      'hipercard'  => __( 'Hipercard', 'woocommerce-rakuten-pay' ),
      'discover'   => __( 'Discover', 'woocommerce-rakuten-pay' ),
    );

    return isset( $names[ $brand ] ) ? $names[ $brand ] : $brand;
  }

  /**
   * Get payment method.
   *
   * @param WC_Order $order WooCommerce Order.
   * @return string
   */
  protected function get_payment_method( $order ) {
    $payment_method = $order->get_payment_method( $order );

    switch ( $payment_method ) {
    case 'rakuten-pay-credit-card':
      return 'credit_card';
    case 'rakuten-pay-banking-billet':
      return 'billet';
    }
  }

  /**
   * Is Credit Card payment method.
   *
   * @param WC_Order $order WooCommerce Order.
   * @return boolean  Returns true if is Credit Card Payment.
   */
  public function is_credit_card_payment_method( $order ) {
    return 'credit_card' === $this->get_payment_method( $order );
  }

  /**
   * Is Billet payment method.
   *
   * @param WC_Order $order WooCommerce Order.
   * @return boolean  Returns true if is Credit Card Payment.
   */
  public function is_banking_billet_payment_method( $order ) {
    return 'billet' === $this->get_payment_method( $order );
  }

  /**
   * Save order meta fields for credid card payment type.
   * Save fields as meta data to display on order's admin screen.
   *
   * @param int    $id Order ID.
   * @param array  $data Order data.
   */
  protected function save_order_meta_fields( $id, $data, $transaction ) {
    $payments = array_shift($transaction['payments']);

    if ( ! empty( $data['card_brand'] ) ) {
      update_post_meta( $id, __( 'Credit Card', 'woocommerce-rakuten-pay' ), $this->get_card_brand_name( sanitize_text_field( $data['card_brand'] ) ) );
    }
    if ( ! empty( $data['installments'] ) ) {
      update_post_meta( $id, __( 'Installments', 'woocommerce-rakuten-pay' ), sanitize_text_field( $data['installments'] ) );
    }
    if ( ! empty( $data['amount'] ) ) {
      update_post_meta( $id, __( 'Total paid', 'woocommerce-rakuten-pay' ), number_format( intval( $data['amount'] ), wc_get_price_decimals(), wc_get_price_decimal_separator(), wc_get_price_thousand_separator() ) );
    }
    if ( ! empty( $data['billet_url'] ) ) {
      update_post_meta( $id, __( 'Banking Ticket URL', 'woocommerce-rakuten-pay' ), sanitize_text_field( $data['billet_url'] ) );
    }
    if ( ! empty( $payments['credit_card']['number'] ) ) {
      update_post_meta( $id, __( 'Card Number', 'woocommerce-rakuten-pay' ), sanitize_text_field( $payments['credit_card']['number'] ) );
    }
  }

  /**
   * Process payment by method.
   *
   * @param int $order_id Order ID.
   *
   * @return array Redirect data.
   */
  public function process_regular_payment( $order_id ) {
    $order          = wc_get_order( $order_id );
    $payment_method = $this->get_payment_method( $order );

    $installments_qty = (integer) $_POST['rakuten_pay_installments'];
    $amount           = (float) $order->get_total();
    $installment      = null;
    if ( $installments_qty > $this->gateway->free_installments ) {
      $installments = $this->get_installments( $amount );
      if ( $installments === false ) {
        return array(
          'result' => 'fail'
        );
      }
      foreach ($installments as $i) {
        if ( $i['quantity'] == $installments_qty ) {
          $installment = $i;
          break;
        }
      }
    }

    $data           = $this->generate_charge_data( $order, $payment_method, $_POST, $installment );
    $transaction    = $this->charge_transaction( $order, $data );
    $payments = array_shift($transaction['payments']);

    if ( isset( $transaction['result'] ) && $transaction['result'] === 'fail' ) {
      return $transaction;
    }

    if ( isset( $transaction['errors'] ) ) {
      foreach ( $transaction['errors'] as $error ) {
        $error_msg = $error['code'] . ', ' . $error['description'];
        wc_add_notice( $error_msg, 'error' );
      }

      return array(
        'result' => 'fail',
      );
    }

    if ( ! isset( $transaction['charge_uuid'] ) ) {
      if ( 'yes' === $this->gateway->debug ) {
        $this->gateway->log->add( $this->gateway->id, 'Transaction data does not contain id or charge url for order ' . $order->get_order_number() . '...' );
      }

      return array(
        'result' => 'fail',
      );
    }

    // Save transaction data.
    update_post_meta( $order_id, '_wc_rakuten_pay_transaction_id', $transaction['charge_uuid'] );
    update_post_meta( $order_id, '_transaction_id', $transaction['charge_uuid'] );

    if ( $payment_method === 'credit_card' ) {
      $payment_data = array(
        'payment_method'  => $payment_method,
        'installments'    => $_POST['rakuten_pay_installments'],
        'card_brand'      => $this->get_card_brand_name( $_POST['rakuten_pay_card_brand'] ),
        'amount'          => $data['amount'],
        'number'          => $payments['credit_card']['number']
      );
    } else {
      $payment_data = array(
        'payment_method'  => $payment_method,
        'billet_url'      => $this->banking_billet_url( $transaction['charge_uuid'] ),
        'amount'          => $data['amount']
      );
    }

    $payment_data = array_map(
      'sanitize_text_field',
      $payment_data
    );

    update_post_meta( $order_id, '_wc_rakuten_pay_transaction_data', $payment_data );
    $this->save_order_meta_fields( $order_id, $payment_data, $transaction );

    // Change the order status.
    $this->process_order_status( $order, $transaction['result'], $transaction );

    // Empty the cart.
    WC()->cart->empty_cart();

    // Redirect to thanks page.
    return array(
      'result'   => 'success',
      'redirect' => $this->gateway->get_return_url( $order ),
    );
  }

  /**
   * Process refund.
   *
   * @param int    $order_id  Order ID.
   * @param float  $amount    Amount to refund.
   * @param string $reason    Reason whereby the refund has been done.
   *
   * @return array Redirect data.
   *
   */
  public function process_refund( $order_id, $amount, $reason ) {
    $order            = wc_get_order( $order_id );
    $payment_method   = $this->get_payment_method( $order );
    $transaction_data = $this->get_transaction( $order );

    if ( ! $transaction_data ) {
      return false;
    }

    $refund_result  = $this->generate_refund_data( $order, $payment_method, $_POST, $transaction_data );
    $refund_kind    = $refund_result[0];
    $refund_data    = $refund_result[1];
    $result         = $this->refund_transaction( $order, $refund_kind, $refund_data );

    return $result;
  }

  /**
   * Check if Rakuten Pay response is valid.
   *
   * @param  string $body  IPN body.
   * @param  string $token IPN signature token
   *
   * @return bool
   */
  public function verify_signature( $body, $token ) {
    $signature  = $this->get_signature( $body );
    error_log(print_r($signature, true));
    return $token === $signature;
  }

  /**
   * Send email notification.
   *
   * @param string $subject Email subject.
   * @param string $title   Email title.
   * @param string $message Email message.
   */
  protected function send_email( $subject, $title, $message ) {
    $mailer = WC()->mailer();
    $mailer->send( get_option( 'admin_email' ), $subject, $mailer->wrap_message( $title, $message ) );
  }

  /**
   * Process banking billet.
   *
   * @param string $billet  Billet id number.
   */
  public function process_banking_billet( $billet ) {
    @ob_clean();

    $response = $this->do_get_request( 'charges/' . $billet . '/billet/download', array(
      'Authorization' => $this->authorization_header(),
      'Content-Type' => 'application/json'
    ) );

    $data = json_decode( $response['body'], true );

    echo $data['html'];
    exit;
  }

  /**
   * IPN handler.
   */
  public function ipn_handler() {
    @ob_clean();

    $raw_response = file_get_contents( 'php://input' );

    if ( empty( $raw_response ) ) {
      return $this->ipn_handler_fail();
    }

    $token = $_SERVER['HTTP_SIGNATURE'];
    if ( ! $this->verify_signature( $raw_response, $token ) ) {
      return $this->ipn_handler_fail();
    }

    $decoded_response = json_decode( $raw_response, true );
    $ipn_result = $this->process_ipn( $decoded_response );

    if ( ! $ipn_result ) {
      return $this->ipn_handler_fail();
    }

    header( 'HTTP/1.1 200 OK' );

    // Deprecated action since 2.0.0.
    do_action( 'wc_rakuten_pay_valid_ipn_request', $decoded_response );

    exit;
  }

  protected function ipn_handler_fail() {
    wp_die( esc_html__( 'Rakuten Pay Request Failure', 'woocommerce-rakuten-pay' ), '', array( 'response' => 401 ) );
  }

  /**
   * Process IPN requests.
   *
   * @param array    $posted Posted data.
   *
   * @return boolean $result Result of ipn process
   */
  public function process_ipn( $posted ) {
    global $wpdb;

    $posted   = wp_unslash( $posted );
    $order_id = absint( $wpdb->get_var( $wpdb->prepare( "SELECT post_id FROM $wpdb->postmeta WHERE meta_key = '_wc_rakuten_pay_transaction_id' AND meta_value = %s", $posted['uuid'] ) ) );
    $order    = wc_get_order( $order_id );
    $status   = $this->normalize_status( $posted['status'] );

    if ( ! $order ) {
      return false;
    }

    if ( $order->get_id() !== $order_id ) {
      return false;
    }

    $order_status_result = $this->process_order_status( $order, $status, $posted );
    return $order_status_result;
  }

  /**
   * Process the order status.
   *
   * @param  WC_Order $order  Order data.
   * @param  string   $status Transaction status.
   *
   * @return boolean  $result Order status result
   */
  public function process_order_status( $order, $status, $data ) {
    if ( 'yes' === $this->gateway->debug ) {
      $this->gateway->log->add( $this->gateway->id, 'Payment status for order ' . $order->get_order_number() . ' is now: ' . $status );
    }

    switch ( $status ) {
      case 'pending' :
        $order->update_status( 'on-hold', __( 'Rakuten Pay: The transaction is being processed.', 'woocommerce-rakuten-pay' ) );

        break;
      case 'authorized' :
        if ( in_array( $order->get_status(), array( 'approved', 'completed' ), true ) ) {
          break;
        }

        $order->update_status( 'on-hold', __( 'Rakuten Pay: The transaction was authorized.', 'woocommerce-rakuten-pay' ) );

        break;
      case 'approved' :
        if ( in_array( $order->get_status(), array( 'completed' ), true ) ) {
          break;
        }

        $order->add_order_note( __( 'Rakuten Pay: Transaction paid.', 'woocommerce-rakuten-pay' ) );

        // Changing the order for processing and reduces the stock.
        $order->payment_complete();

        break;
      case 'cancelled' :
        if ( in_array( $order->get_status(), array( 'approved', 'completed' ), true ) ) {
          break;
        }
        if ( get_post_meta( $order->get_id(), '_wc_rakuten_pay_order_cancelled', true ) ) {
          break;
        }

        update_post_meta( $order->get_id(), '_wc_rakuten_pay_order_cancelled', 'yes' );
        $order->update_status( 'cancelled' );

        $transaction_id  = get_post_meta( $order->get_id(), '_wc_rakuten_pay_transaction_id', true );
        $transaction_url = '<a href="https://dashboard.rakuten.com.br/sales/' . intval( $transaction_id ) . '">https://dashboard.rakuten.com.br/sales/' . intval( $transaction_id ) . '</a>';
        $this->send_email(
          sprintf( esc_html__( 'The transaction for order %s was cancelled', 'woocommerce-rakuten-pay' ), $order->get_order_number() ),
          esc_html__( 'Transaction failed', 'woocommerce-rakuten-pay' ),
          sprintf( esc_html__( 'Order %1$s has been marked as cancelled, because the transaction was cancelled on Rakuten Pay, for more details, see %2$s.', 'woocommerce-rakuten-pay' ), $order->get_order_number(), $transaction_url )
        );

        $order->add_order_note( __( 'Rakuten Pay: The transaction was cancelled.', 'woocommerce-rakuten-pay' ) );

        break;

      case 'refunded' :
        if ( in_array( $order->get_status(), array( 'on-hold' ), true ) ) {
          break;
        }
        if ( (float) $order->get_total() === (float) $order->get_total_refunded() ) {
          break;
        }

        $refunded_ids = get_post_meta( $order->get_id(), '_wc_rakuten_pay_order_refunded_ids', true );
        $refunded_ids = $refunded_ids ?: array();

        if ( isset( $data['refunds'] ) ) {
          foreach ( $data['refunds'] as $refund_data ) {

            $next_refund = false;
            foreach ( $refunded_ids as $refunded_id ) {
              if ( $refunded_id === $refund_data['id'] ) {
                $next_refund = true;
              }
            }

            if ( $next_refund ) {
              continue;
            }

            $refunded_ids[] = $refund_data['id'];
            update_post_meta( $order->get_id(), '_wc_rakuten_pay_order_refunded_ids', $refunded_ids );

            $refund_amount          = $refund_data['amount'];
            $refund_reason          = $refund_data['reason'];
            $order_id               = $order->get_id();
            $api_refund             = 0; // via ipn
            $restock_refunded_items = 1;

            $refund = wc_create_refund( array(
              'amount'         => $refund_amount,
              'reason'         => $refund_reason,
              'order_id'       => $order_id,
              'line_items'     => array(),
              'refund_payment' => $api_refund,
              'restock_items'  => $restock_refunded_items,
            ) );

            if ( is_wp_error( $refund ) ) {
              if ( 'yes' === $this->gateway->debug ) {
                $this->gateway->log->add( $this->gateway->id, 'WP_Error in refund status processing: ' . $response->get_error_message() . '...' );
              }
              return false;
            }
          }
        } else {
          $refund_amount          = wc_format_decimal( $order->get_total() - $order->get_total_refunded() );
          $refund_reason          = __( 'Order fully refunded', 'woocommerce' );
          $order_id               = $order->get_id();
          $api_refund             = 1; // via ipn
          $restock_refunded_items = 1;

          $refund = wc_create_refund( array(
            'amount'         => $refund_amount,
            'reason'         => $refund_reason,
            'order_id'       => $order_id,
            'line_items'     => array(),
            'refund_payment' => $api_refund,
            'restock_items'  => $restock_refunded_items,
          ) );

          if ( is_wp_error( $refund ) ) {
            if ( 'yes' === $this->gateway->debug ) {
              $this->gateway->log->add( $this->gateway->id, 'WP_Error in refund status processing: ' . $response->get_error_message() . '...' );
            }
            return false;
          }
        }

        if ( (float) $order->get_total() === (float) $order->get_total_refunded() ) {
          $order->add_order_note( __( 'Rakuten Pay: The transaction fully refunded.', 'woocommerce-rakuten-pay' ) );
          // $order->update_status( 'refunded', __( 'Rakuten Pay: The transaction was fully refunded.', 'woocommerce-rakuten-pay' ) );
        } else {
          $order->add_order_note( __( 'Rakuten Pay: The transaction received a partial refund.', 'woocommerce-rakuten-pay' ) );
        }

        $transaction_id  = get_post_meta( $order->get_id(), '_wc_rakuten_pay_transaction_id', true );
        $transaction_url = '<a href="https://dashboard.rakuten.com.br/sales/' . intval( $transaction_id ) . '">https://dashboard.rakuten.com.br/sales/' . intval( $transaction_id ) . '</a>';
        $this->send_email(
          sprintf( esc_html__( 'The transaction for order %s refunded', 'woocommerce-rakuten-pay' ), $order->get_order_number() ),
          esc_html__( 'Transaction refunded', 'woocommerce-rakuten-pay' ),
          sprintf( esc_html__( 'Order %1$s has been marked as refunded by Rakuten Pay, for more details, see %2$s.', 'woocommerce-rakuten-pay' ), $order->get_order_number(), $transaction_url )
        );

        break;

      default :
        break;
    }

    return true;
  }

  /**
   * get signature of requested data.
   *
   * @param   string  $data  Data.
   * @return  string  base64 signature.
   */
  private function get_signature( $data ) {
    $signature = hash_hmac(
      'sha256',
      $data,
      $this->gateway->signature_key,
      true
    );
    return base64_encode( $signature );
  }

  /**
   * Base64 encoding without padding
   *
   * @param   string   $data   Data to encode
   * @return  string           Base64 encoded data
   */
  private function base64_encode_url( $data ) {
    return rtrim( strtr( base64_encode( $data ), '+/', '-_'), '=' );
  }

  /**
   * Customer IP address.
   *
   * @param  WC_Order   Current Order
   * @return string     Customer ip address
   */
  private function customer_ip_address( $order ) {
    return get_post_meta( $order->get_id(), '_customer_ip_address', true );
  }

  /**
   * Banking Billet URL
   *
   * @param  string $billet  Billet id number.
   * @return string          Billet URL.
   */
  public function banking_billet_url( $billet ) {
    $scheme = parse_url( home_url(), PHP_URL_SCHEME );
    $query = array(
      'wc-api' => get_class( $this->gateway ),
      'billet' => $billet
    );
    $api_request_url = add_query_arg( $query, trailingslashit( home_url( '', $scheme ) ) );
    return $api_request_url;
  }

  /**
   * strtotime considering the wp timezone
   * @param string  $time date time format like defined on std strtotime
   * @return int    unix timestamp
   */
  public function strtotime( $time ) {
    $tz_string = get_option('timezone_string');
    $tz_offset = get_option('gmt_offset', 0);

    if ( !empty( $tz_string ) ) {
      // If site timezone option string exists, use it
      $timezone = $tz_string;

    } elseif ( $tz_offset == 0 ) {
      // get UTC offset, if it isn’t set then return UTC
      $timezone = 'UTC';

    } else {
      $timezone = $tz_offset;

      if( substr( $tz_offset, 0, 1 ) != "-" && substr( $tz_offset, 0, 1 ) != "+" && substr( $tz_offset, 0, 1 ) != "U" ) {
        $timezone = "+" . $tz_offset;
      }
    }

    $datetime = new DateTime($time, new DateTimeZone($timezone));
    return $datetime->format('U');
  }

  private function normalize_status( $status ) {
    $status = sanitize_text_field( $status );

    switch ( $status ) {
    case 'partial_refunded':
      return 'refunded';
    default:
      return $status;
    }
  }

  private function authorization_header() {
    $document  = $this->gateway->document;
    $api_key   = $this->gateway->api_key;
    $user_pass = $document . ':' . $api_key;
    return 'Basic ' . base64_encode( $user_pass );
  }
}

