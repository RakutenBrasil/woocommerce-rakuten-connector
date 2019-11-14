<?php
/**
 * Rakuten Log Admin Orders List
 *
 * @package WC_Rakuten_Log
 */

if ( ! defined('ABSPATH') ){
    exit;
}

class WC_Rakuten_Log_Admin_Orders extends WC_Shipping_Method {

    public function __construct()
    {
        add_action( 'restrict_manage_posts', array($this, 'display_shipping_dropdown') );
        add_action( 'add_meta_boxes', array($this, 'register_metabox'));
        add_action( 'admin_notices', array($this, 'rakuten_batch_admin_notice') );
        add_action( 'woocommerce_process_shop_order_meta', array($this, 'rakuten_log_save_shop_order') );
        add_action( 'current_screen', function(){
            $_SERVER['REQUEST_URI'] = remove_query_arg( ['success', 'errors'], $_SERVER['REQUEST_URI'] );
        } );

        add_filter( 'posts_where', array($this, 'admin_rakuten_shipping_filter'), 10, 2 );
        add_filter( 'bulk_actions-edit-shop_order', array($this, 'admin_rakuten_log_batch') );
        add_filter( 'handle_bulk_actions-edit-shop_order', array($this, 'do_post_rakuten_log_batch'), 10, 3 );

        add_filter('manage_edit-shop_order_columns', array($this, 'show_rakuten_log_columns'), 15);
        add_filter('manage_shop_order_posts_custom_column', array($this, 'tracking_code_orders_list'), 100);
    }

    public function register_metabox() {
        add_meta_box(
            'wc_rakuten_log',
            'GenLog',
            array($this, 'metabox_content'),
            'shop_order',
            'advanced',
            'default'
        );
    }

    public function display_shipping_dropdown($post_type) {
        if ($post_type !== 'shop_order'){
            return;
        }

        $selected = '';
        $request_attr = 'rakuten_shipping_filter';
        if ( isset($_REQUEST[$request_attr]) ) {
            $selected = $_REQUEST[$request_attr];
        }

        $shipping_methods = WC()->shipping->get_shipping_methods();

        echo '<select id="rakuten-shipping-filter" name="rakuten_shipping_filter">';
        echo '<option value="0">' . __( 'Show all shipping methods', 'woocommerce-rakuten-log') . '</option>';
        foreach ($shipping_methods as $shipping_method){
            $select = ($shipping_method->id === $selected) ? 'selected="selected"':'';
            echo '<option value="' .$shipping_method->id . '"'. $select . '>' . $shipping_method->get_method_title() . '</option>';
        }
        echo '</select>';

        return $shipping_methods;
    }

    public function admin_rakuten_shipping_filter( $where, &$wp_query )
    {
        global $pagenow;
        $request_attr = 'rakuten_shipping_filter';
        if ( isset($_REQUEST[$request_attr]) ) {
            $selected = $_REQUEST[$request_attr];
        }

        if ( is_admin() && $pagenow=='edit.php' && $wp_query->query_vars['post_type'] == 'shop_order' && !empty($selected) ) {
            $where .= $GLOBALS['wpdb']->prepare( 'AND ID
                IN (
                    SELECT order_id
                    FROM wp_woocommerce_order_items
                    WHERE order_item_id IN (
                        SELECT order_item_id
                        FROM wp_woocommerce_order_itemmeta
                        WHERE meta_key = %s
                        AND meta_value LIKE %s
                    )
                )', 'method_id',  '%' . $GLOBALS['wpdb']->esc_like($selected) . '%'
            );
        }

        return $where;
    }

    public function admin_rakuten_log_batch($actions) {
        $actions['rakuten-log-batch'] = __("Create GenLog batch", "woocommerce-rakuten-log");

        return $actions;
    }

    public function do_post_rakuten_log_batch( $redirect_to, $doaction, $order_ids ) {
        if ( $doaction !== 'rakuten-log-batch' ){
            return $redirect_to;
        }

        $batch_payload = [];

        foreach($order_ids as $order_id){
            $order = new WC_Order($order_id);
            $customer = new WC_Customer($order->get_customer_id());
            $shipping_methods = $order->get_shipping_methods();
            $shipping_data = reset($shipping_methods);
            $errors = $this->valid_rakuten_log_batch_orders($order_ids);
	        $charge_uuid = get_post_meta($order_id, '_wc_rakuten_pay_transaction_id');
	        $document = get_post_meta($order->get_id(), '_billing_cpf');
	        $total_value = (float) $order->get_shipping_total();
	        $district = get_post_meta($order_id, '_shipping_neighborhood');

            if(empty($errors)){
                $batch_item = array(
                    'calculation_code' => $shipping_data->get_meta('calculation_code'),
                    'postage_service_code' => $shipping_data->get_meta('postage_service_code'),
                    'order' => array(
                        'code' => (string) $order->get_id(),
                        'customer_order_number' => $order->get_id(),
                        'payments_charge_id' => $charge_uuid[0],
                        'total_value' => $total_value,
                        'delivery_address' => array (
                            'first_name' => $order->get_shipping_first_name(),
                            'last_name' => $order->get_shipping_last_name(),
                            'street' => $order->get_shipping_address_1(),
                            'number' => $order->get_meta('_shipping_number'),
                            'complement' => $order->get_shipping_address_2(),
                            'district' => $district[0],
                            'city' => $order->get_shipping_city(),
                            'state' => $order->get_shipping_state(),
                            'zipcode' => str_replace("-", "", $order->get_shipping_postcode()),
                            'email' => $order->get_billing_email(),
                            'phone' => $order->get_billing_phone()
                        ),
                        'customer' => array(
                            'first_name' => $customer->get_first_name(),
                            'last_name' => $customer->get_last_name(),
                            'cpf' => $document[0],
                        ),
                        'invoice' => array(
                            'series' => wc_rakuten_log_get_invoice_series($order),
                            'number' => wc_rakuten_log_get_invoice_number($order),
                            'key'    => wc_rakuten_log_get_invoice_key($order),
                            'cfop'   => wc_rakuten_log_get_invoice_cfop($order),
                            'date'   => wc_rakuten_log_get_invoice_date($order)
                        )
                    )
                );

                $batch_payload[] = $batch_item;
            } else {
                $redirect_to = add_query_arg('errors', $errors, $redirect_to);
            }
        }
        $this->log = new WC_Logger();
	    $query = $GLOBALS['wpdb']->get_results( "SELECT instance_id, method_id FROM {$GLOBALS['wpdb']->prefix}woocommerce_shipping_zone_methods WHERE method_id = 'rakuten-log' " );
        foreach ( $query as $dado ) {
            $instance_id = $dado->instance_id;
	        $rakuten_log_shipping = new WC_Rakuten_Log_REST_Client($instance_id);
        }

        $result = $rakuten_log_shipping->create_batch($batch_payload, $order_id, $order_ids);

        if( !isset($result['result']) || $result['result'] !== 'fail' ){
            foreach ($result['content'] as $content){
                foreach ($content['tracking_objects'] as $tracking_object){
                    $order_id = $tracking_object["order_code"];
                    $order = wc_get_order( $order_id );
                    wc_rakuten_log_update_tracking_code($order, $tracking_object['number']);
                    wc_rakuten_log_update_tracking_url($order, $tracking_object['tracking_url']);
                    wc_rakuten_log_update_print_url($order, $tracking_object['print_url']);
                    wc_rakuten_log_update_batch_print_url($order, $result['content'][0]['print_url']);
                    wc_rakuten_log_update_batch_code($order, $result['content'][0]['code']);
                    wc_rakuten_log_update_volume($order, $tracking_object['volume_number']);
                    wc_rakuten_log_trigger_tracking_email($order, $tracking_object['tracking_url']);
                }
            }
            $redirect_to = add_query_arg('success', true, $redirect_to);
        } else {
            $errors = array();

            if (isset($result['errors'])){
                foreach($result['errors'] as $error){
                    $errors[] = array(
                        'message' => $error['text']
                    );
                }

            } else {
                $errors[] = array(
                    'message' => 'Failure on the communication with the GenLog API'
                );

            }

            $redirect_to = add_query_arg('errors', $errors, $redirect_to);
        }

        return $redirect_to;
    }

    public function show_rakuten_log_columns( $columns ){
        $new_columns = array();

        foreach ($columns as $column_name => $column_info) {
            $new_columns[ $column_name ] = $column_info;

            if ( 'order_status' === $column_name ) {
                $new_columns['tracking_code'] = __('Tracking Code', 'woocommerce-rakuten-log');
            }
        }

        return $new_columns;
    }

    public function tracking_code_orders_list( $column ){
        global $post, $the_order;

        if ( 'tracking_code' === $column ) {
            if ( empty( $the_order ) || $the_order->get_id() !== $post->ID ) {
                $the_order = wc_get_order( $post->ID );
            }

            $code =  wc_rakuten_log_get_tracking_code($the_order);
            $print_url = wc_rakuten_log_get_print_url($the_order);

            if (!empty($code)){
                $tracking_code = '<a href="' . esc_html($print_url) . '" aria-label="' . esc_attr__('Tracking Code', 'woocommerce-rakuten-log') . '" target="_blank">' . esc_html($code) . '</a>';

                include dirname( __FILE__ ) . '/views/html-list-table-tracking-code.php';
            }
        }
    }

    public function metabox_content( $post ) {
        $tracking_code = wc_rakuten_log_get_tracking_code($post->ID );
        $tracking_url = wc_rakuten_log_get_tracking_url( $post-> ID );
        $print_url = wc_rakuten_log_get_print_url( $post-> ID );
        $volume = wc_rakuten_log_get_volume( $post-> ID );
        $print_batch_url = wc_rakuten_log_get_batch_print_url( $post-> ID );
        $batch_code = wc_rakuten_log_get_batch_code( $post->ID );
        $invoice_series = wc_rakuten_log_get_invoice_series( $post->ID );
        $invoice_number = wc_rakuten_log_get_invoice_number( $post->ID );
        $invoice_key = wc_rakuten_log_get_invoice_key( $post->ID );
        $invoice_cfop = wc_rakuten_log_get_invoice_cfop( $post->ID );
        $invoice_date = wc_rakuten_log_get_invoice_date( $post->ID );

        include_once dirname(__FILE__) . '/views/html-metabox-shop-order.php';
    }

    public function valid_rakuten_log_batch_orders( $order_ids ){
        $errors = array();

        foreach($order_ids as $order_id) {
            $order = new WC_Order($order_id);
            $shipping_methods = $order->get_shipping_methods();
            $shipping_data = reset($shipping_methods);
            if (strpos($shipping_data->get_method_id(),'rakuten-log') === false) {
                $errors[] = array(
                    'message' => __("Invalid shipping method", "woocommerce-rakuten-log"),
                    'order_id' => $order_id
                );
            }
            if (!empty(wc_rakuten_log_get_tracking_code($order_id))){
                $errors[] = array(
                    'message' => __("Batch already created for order", "woocommerce-rakuten-log"),
                    'order_id' => $order_id
                );
            }
        }

        return $errors;
    }

    public function rakuten_batch_admin_notice() {
        if ( !empty($_REQUEST['errors'])) {
            $errors = $_REQUEST['errors'];
            ?>
            <div class="error notice">
                <p><?php echo esc_html(__('There has been an error creating a GenLog Batch!', 'woocommerce-rakuten-log')) ?></p>
                <ul>
                <?php foreach ($errors as $error): ?>
                    <?php if(isset($error['order_id'])){ ?>
                        <li><?php echo esc_html($error['message'] . ', order_id: ' . $error['order_id']) ?></li>
                    <?php }else{ ?>
                        <li><?php echo esc_html($error['message']) ?></li>
                    <?php } ?>
                <?php endforeach; ?>
                </ul>
            </div>
            <?php
        } else if ( !empty($_REQUEST['success'])){
            ?>
            <div class="updated notice">
                <p><?php echo esc_html(__('Batch successfully created', 'woocommerce-rakuten-log')) ?></p>
            </div>
            <?php

        }
    }

    public function rakuten_log_save_shop_order( $post_id ) {
        if( isset( $_POST['_rakuten_log_invoice_series']) ){
            wc_rakuten_log_update_invoice_series( $post_id, $_POST['_rakuten_log_invoice_series'] );
        }
        if( isset( $_POST['_rakuten_log_invoice_number']) ){
            wc_rakuten_log_update_invoice_number( $post_id, $_POST['_rakuten_log_invoice_number'] );
        }
        if( isset( $_POST['_rakuten_log_invoice_key']) ){
            wc_rakuten_log_update_invoice_key( $post_id, $_POST['_rakuten_log_invoice_key'] );
        }
        if( isset( $_POST['_rakuten_log_invoice_cfop']) ){
            wc_rakuten_log_update_invoice_cfop( $post_id, $_POST['_rakuten_log_invoice_cfop'] );
        }
        if( isset( $_POST['_rakuten_log_invoice_date']) ){
            wc_rakuten_log_update_invoice_date( $post_id, $_POST['_rakuten_log_invoice_date'] );
        }
    }

}

new WC_Rakuten_Log_Admin_Orders();