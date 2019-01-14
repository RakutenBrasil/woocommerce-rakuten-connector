<?php
/**
 * Rakuten Log Customer Order Details
 *
 * @package WC_Rakuten_Log
 */

if (!defined('ABSPATH')) {
    exit;
}

class WC_Rakuten_Log_Order_Details {
    public function __construct()
    {
        add_action('woocommerce_order_details_after_order_table', array($this, 'display_tracking_link'), 1);
    }

    public function display_tracking_link( $order )
    {
        $tracking_link = wc_rakuten_log_get_tracking_url( $order );
        $tracking_code = wc_rakuten_log_get_tracking_code( $order );

        if (empty($tracking_link)){
            return;
        }

        wc_get_template(
            'myaccount/tracking_link.php',
            array(
                'tracking_code' => $tracking_code,
                'tracking_url' => $tracking_link
            ),
            '',
            WC_Rakuten_Log::get_templates_path()
        );
    }
}

new WC_Rakuten_Log_Order_Details();