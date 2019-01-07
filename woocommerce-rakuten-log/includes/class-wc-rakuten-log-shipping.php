<?php
/**
 * Rakuten Log Shipping Method
 *
 * @package WC_Rakuten_Log
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class WC_Rakuten_Log_Shipping extends WC_Shipping_Method {
    public function __construct($instance_id = 0)
    {
        $this->instance_id = absint( $instance_id );
        $this->id = 'rakuten-log';
        $this->method_title = __('Rakuten Log', 'woocommerce-rakuten-log');

        $this->method_description = sprintf( __('%s is a shipping method from Rakuten Log.', 'woocommerce-rakuten-log'), $this->method_title);
        $this->supports           = array(
            'shipping-zones',
            'instance-settings',
        );

        $this->init_form_fields();

        $this->enabled = $this->get_option('enabled');
        $this->title   = $this->get_option( 'title' );
        $this->owner_document = $this->get_option('owner_document');
        $this->api_key = $this->get_option('api_key');
        $this->signature_key = $this->get_option('signature_key');
        $this->environment = $this->get_option('environment');

        $this->rest_client = new WC_Rakuten_Log_REST_Client( $this );

        add_action( 'woocommerce_update_options_shipping_' . $this->id, array( $this, 'process_admin_options' ) );
    }

    public function init_form_fields()
    {
        $this->instance_form_fields = array(
            'title' => array (
                'title'    => __('Title', 'woocommerce-rakuten-log'),
                'type'     => 'text',
                'label'    => __('This option controls what the customer will see during checkout', 'woocommerce-rakuten-log'),
                'desc_tip' => true,
                'default'  => $this->method_title
            ),
            'owner_document' => array (
                'title'    => __('Owner Document', 'woocommerce-rakuten-log'),
                'type'     => 'text',
                'label'    => __('Document of the owner registered on Rakuten', 'woocommerce-rakuten-log'),
                'desc_tip' => true,
                'default'  => ''
            ),
            'api_key' => array(
                'title'    =>  __('API key', 'woocommerce-rakuten-log'),
                'type'     => 'text',
                'label'    => __('API key registered on Rakuten', 'woocommerce-rakuten-log'),
                'desc_tip' => true,
                'default'  => ''
            ),
            'signature_key' => array(
                'title'    => __('Signature Key', 'woocommerce-rakuten-log'),
                'type'     => 'text',
                'label'    => __('Signature key registered on Rakuten', 'woocommerce-rakuten-log'),
                'desc_tip' => true,
                'default'  => ''
            ),
            'environment' => array(
                'title'       => __('Environment', 'woocommerce-rakuten-log'),
                'type'        => 'select',
                'description' => sprintf( __( 'Rakuten Log has two environments, the Sandbox used to make test transactions, and Production used for real transactions.', 'woocommerce-rakuten-log' ) ),
                'default'     => 'sandbox',
                'options'     => array(
                    'production'  => sprintf( __( 'Production', 'woocommerce-rakuten-log' ) ),
                    'sandbox'     => sprintf( __( 'Sandbox', 'woocommerce-rakuten-log' ) )
                )
            )
        );
    }

    public function calculate_shipping($package = array())
    {
        $calculation_data = array();

        if (!empty($package['destination']['postcode'])) {
            $calculation_data['destination_zipcode'] = str_replace("-", "", $package['destination']['postcode']);
            $calculation_data['postage_service_codes'] = array(); #always return all postage service codes

            $products = array();

            foreach($package['contents'] as $item_id => $values) {
                if(!$values['data']->needs_shipping()) {
                    continue;
                }

                $product = array(
                    'code' => $values['data']->get_sku(),
                    'name' => $values['data']->get_name(),
                    'cost' => $values['data']->get_price(),
                    'quantity' => $values['quantity'],
                    'dimensions' => array(
                        'weight' => $values['data']->get_weight(),
                        'width' => $values['data']->get_width(),
                        'height' => $values['data']->get_height(),
                        'length' => $values['data']->get_length()
                    )
                );

                $products[] = $product;
            }

            $calculation_data['products'] = $products;

            $shipping_methods = $this->rest_client->create_calculation($calculation_data);

            if(!isset($shipping_methods['result']) || $shipping_methods['result'] !== 'fail'){
                foreach($shipping_methods['content']['shipping_options'] as $shipping_method){
                    $rate = array(
                        'id' => 'rakuten-log:' . $shipping_method['postage_service_code'],
                        'label' => $shipping_method['logistics_operator_type'] . ' - ' . strtolower($shipping_method['postage_service_name']),
                        'cost' => $shipping_method['final_cost'],
                        'meta_data' => array(
                            'calculation_code' => $shipping_methods['content']['code'],
                            'postage_service_code' => $shipping_method['postage_service_code'],
                            'instance_id' => $this->instance_id
                        )
                    );

                    $this->add_rate($rate);
                }

            }
        }
    }

    public function create_batch($batch){
        return $this->rest_client->create_batch($batch);
    }
}
