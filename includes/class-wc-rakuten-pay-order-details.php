<?php
/**
 * Rakuten Pay Customer Order Details
 *
 * @package WC_Rakuten_Pay
 */

if (!defined('ABSPATH')) {
    exit;
}

class WC_Rakuten_Pay_Order_Details {
    public function __construct()
    {
        // Account Edit Addresses: Remove and reorder addresses fields
        add_filter( 'woocommerce_default_address_fields', array( $this, 'rk_myaccount_address_fields' ), 20, 1 );
        // Account Edit Addresses: Reorder billing email and phone fields
        add_filter( 'woocommerce_billing_fields', array( $this, 'rk_myaccount_billing_fields'), 20, 1 );
    }

    public function rk_myaccount_address_fields( $fields ) {
        // Only on account pages
        if( ! is_account_page() ) return $fields;

        // Set the order (sorting fields) in the array below
        $sorted_fields = array('first_name','last_name','company','postcode', 'address_1', 'address_2', 'country','city','state', 'country');


        $new_fields = array();
        $priority = 0;

        // Reordering billing and shipping fields
        foreach($sorted_fields as $key_field){
            $priority += 10;

            if( $key_field == 'company' )
                $priority += 20; // keep space for email and phone fields

            $new_fields[$key_field] = $fields[$key_field];
            $new_fields[$key_field]['priority'] = $priority;
        }
        return $new_fields;
    }


    public function rk_myaccount_billing_fields( $fields ) {
        // CEP validation and auto complete
        $cep_validation = get_option( 'rakuten_cep_validation' );
        if( $cep_validation == "1") {
            wp_enqueue_script( 'cep-validation', plugins_url( 'assets/js/cep-validation' . '.js', plugin_dir_path( __FILE__ ) ), array ( 'jquery' ), null );
        } else {
            echo "<script>console.log('sem cep')</script>";
        }

        // Only on account pages
        if( ! is_account_page() ) return $fields;

        $billing_fields = $fields;


        $billing_fields = array_merge(
            $billing_fields,
            array(
                'billing_birthdate' => array(
                    'label'           => __( 'Birthdate', 'woocommerce-rakuten-pay' ),
                    'placeholder'     => __( 'Data de nascimento', 'placeholder', 'woocommerce-rakuten-pay' ),
                    'required'        => true,
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
        $billing_fields['billing_document']['priority'] = 50;
        $billing_fields['billing_address_number']['priority'] = 80;
        $billing_fields['billing_district']['priority'] = 100;

        $billing_fields['billing_phone']['required'] = true;


        $fields['billing_email']['priority'] = 30;
        $fields['billing_email']['class'] = array('form-row-first');
        $fields['billing_phone']['priority'] = 40;
        $fields['billing_phone']['class'] = array('form-row-last');

        $fields = $billing_fields;

        return $fields;
    }
}

new WC_Rakuten_Pay_Order_Details();
